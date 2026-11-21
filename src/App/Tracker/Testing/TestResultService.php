<?php

/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2026 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Testing;

use App\Projects\TrackerProject;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Records test results submitted with `/tested` commands on GitHub or with the tracker and publishes them on GitHub.
 *
 * All input paths (GitHub comments, the tracker test form, the maintainer override) go through this class.
 * The database is the source of truth. The section in the pull request description, the `JTracker/HumanTestResults`
 * commit status and the `Tested: …` labels are generated from it by refresh().
 *
 * @since  1.0
 */
class TestResultService
{
    /**
     * Context of the commit status.
     *
     * @var    string
     * @since  1.0
     */
    public const STATUS_CONTEXT = 'JTracker/HumanTestResults';

    /**
     * Name of the label marking a pull request as ready to commit.
     *
     * @var    string
     * @since  1.0
     */
    public const RTC_LABEL = 'RTC';

    /**
     * Status ID "Pending".
     *
     * @var    integer
     * @since  1.0
     */
    public const STATUS_PENDING = 3;

    /**
     * Status ID "Ready To Commit".
     *
     * @var    integer
     * @since  1.0
     */
    public const STATUS_RTC = 4;

    /**
     * Repository permission levels which allow maintainer commands.
     *
     * @var    string[]
     * @since  1.0
     */
    private const MAINTAINER_PERMISSIONS = ['admin', 'maintain', 'write'];

    /**
     * The logger.
     *
     * @var    LoggerInterface
     * @since  1.0
     */
    private LoggerInterface $logger;

    /**
     * Constructor.
     *
     * @param   TrackerProject                    $project      The project.
     * @param   TestResultRepository              $repository   The database access.
     * @param   GithubTestingGateway              $gateway      The GitHub access (as the editbot).
     * @param   PrDescriptionSection              $section      The renderer for the section in the pull request description.
     * @param   TestEnvironmentProviderInterface  $environment  The provider for links to test environments.
     * @param   string                            $botName      The GitHub login of the editbot.
     * @param   string                            $trackerUrl   The base URL of the tracker.
     * @param   LoggerInterface|null              $logger       The logger.
     * @param   TestCommandParser                 $parser       The command parser.
     * @param   DiffFingerprint                   $fingerprint  The diff fingerprint calculator.
     *
     * @since   1.0
     */
    public function __construct(
        private readonly TrackerProject $project,
        private readonly TestResultRepository $repository,
        private readonly GithubTestingGateway $gateway,
        private readonly PrDescriptionSection $section,
        private readonly TestEnvironmentProviderInterface $environment,
        private readonly string $botName,
        private readonly string $trackerUrl = 'https://issues.joomla.org',
        ?LoggerInterface $logger = null,
        private readonly TestCommandParser $parser = new TestCommandParser(),
        private readonly DiffFingerprint $fingerprint = new DiffFingerprint()
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Process an `issue_comment` webhook.
     *
     * @param   object        $hookData  The webhook payload.
     * @param   IssueContext  $issue     The pull request.
     *
     * @return  TestOutcome|null  Null if the comment contains no command.
     *
     * @since   1.0
     */
    public function handleComment(object $hookData, IssueContext $issue): ?TestOutcome
    {
        $comment = $hookData->comment ?? null;
        $action  = (string) ($hookData->action ?? 'created');

        if (!$comment || !isset($comment->id) || !$issue->hasCode) {
            return null;
        }

        $commentId = (int) $comment->id;
        $login     = (string) ($comment->user->login ?? '');

        // Never react to the comments of the bot itself or of other bots
        if ($login === '' || strcasecmp($login, $this->botName) === 0 || ($comment->user->type ?? '') === 'Bot') {
            return null;
        }

        if ($action === 'deleted') {
            return $this->removeByComment($issue, $commentId) ? TestOutcome::accepted() : null;
        }

        $command = $this->parser->parse($comment->body ?? '');

        if ($command === null) {
            // An edited comment no longer containing a command withdraws its result
            if ($action === 'edited' && $this->removeByComment($issue, $commentId)) {
                return TestOutcome::accepted();
            }

            return null;
        }

        $outcome = match ($command->action) {
            TestCommand::RESET   => $this->reset($issue, $login, $command->targetUser),
            TestCommand::INVALID => TestOutcome::rejected(
                'I did not understand this command. Use `/tested ok` or `/tested fail` followed by your notes.'
            ),
            default => $this->record(
                $issue,
                $login,
                (int) $command->getResult(),
                $command->notes,
                $command->environment,
                $commentId,
                'github',
                $command->getReportedSha(),
                $comment->created_at ?? null
            ),
        };

        $this->giveFeedback($issue, $commentId, $login, $outcome, $action);

        return $outcome;
    }

    /**
     * Record a test result.
     *
     * @param   IssueContext  $issue        The pull request.
     * @param   string        $login        The GitHub login of the tester.
     * @param   integer       $result       The result (TestResult::SUCCESS or TestResult::FAILURE).
     * @param   string        $notes        The notes of the tester.
     * @param   array         $environment  The test environment.
     * @param   integer|null  $commentId    The GitHub comment containing the result.
     * @param   string        $source       Where the result has been submitted (github, tracker, alter).
     * @param   string|null   $sha          The tested commit, if reported. Defaults to the head commit.
     * @param   string|null   $date         The date of the test.
     * @param   boolean       $refresh      Whether to update the pull request on GitHub.
     *
     * @return  TestOutcome
     *
     * @since   1.0
     */
    public function record(
        IssueContext $issue,
        string $login,
        int $result,
        string $notes = '',
        array $environment = [],
        ?int $commentId = null,
        string $source = 'github',
        ?string $sha = null,
        ?string $date = null,
        bool $refresh = true
    ): TestOutcome {
        $error = $this->validate($issue, $login, $result, $notes, $source);

        if ($error !== null) {
            return TestOutcome::rejected($error);
        }

        $this->ensureDiffHash($issue);

        // Results count for the head commit. A test of an older commit of the pull request is recorded for that commit.
        $testedSha = $sha !== null ? $issue->findCommit($sha) : null;
        $testedSha = $testedSha ?? $issue->headSha;

        if ($testedSha === null) {
            return TestOutcome::rejected('The commits of this pull request are not known yet, please try again in a minute.');
        }

        $isHead = $testedSha === $issue->headSha;

        $existing = ($commentId ? $this->repository->findByCommentId($commentId) : null)
            ?? $this->repository->findByUser($issue->id, $login, $testedSha);

        // A result can only be changed by the comment of its tester
        if ($existing && strcasecmp($existing->username, $login) !== 0) {
            return TestOutcome::rejected('This comment belongs to another test result.');
        }

        $changed = !$existing || $existing->result !== $result || $existing->sha !== $testedSha;

        $test = $existing ?? new TestResult(null, $issue->id, $login, $result);

        $test->result      = $result;
        $test->sha         = $testedSha;
        $test->testedSha   = $testedSha;
        $test->diffHash    = $isHead ? $issue->diffHash : null;
        $test->createdDate = $this->formatDate($date);
        $test->ghCommentId = $commentId ?? $test->ghCommentId;
        $test->comment     = trim($notes);
        $test->environment = $environment;

        // The webhook for a comment posted by the tracker must not change the source
        if (!$existing || !$commentId || $existing->ghCommentId !== $commentId) {
            $test->source = $source;
        }

        $this->repository->save($test);

        // Only a new or changed result is an activity, so repeating a result does not collect points.
        // A maintainer override is logged by the caller as an activity of the maintainer.
        if ($changed && $source !== 'alter') {
            $this->repository->addActivity(
                'test_item',
                $login,
                (int) $this->project->project_id,
                $issue->number,
                json_encode($result)
            );
        }

        if ($refresh) {
            $this->refresh($issue);
        }

        return TestOutcome::accepted($test);
    }

    /**
     * Check if a test result can be recorded.
     *
     * @param   IssueContext  $issue   The pull request.
     * @param   string        $login   The GitHub login of the tester.
     * @param   integer       $result  The result (TestResult::SUCCESS or TestResult::FAILURE).
     * @param   string        $notes   The notes of the tester.
     * @param   string        $source  Where the result has been submitted (github, tracker, alter).
     *
     * @return  string|null  The reason why the result can not be recorded, null if it can be recorded.
     *
     * @since   1.0
     */
    public function validate(IssueContext $issue, string $login, int $result, string $notes = '', string $source = 'github'): ?string
    {
        if (!$issue->hasCode) {
            return 'Only pull requests can be tested.';
        }

        if (!$issue->open) {
            return 'This pull request is closed, test results are no longer recorded.';
        }

        if ($source !== 'alter' && strcasecmp($login, $issue->author) === 0) {
            return 'The author of a pull request can not test their own pull request.';
        }

        if (!\in_array($result, [TestResult::SUCCESS, TestResult::FAILURE], true)) {
            return 'Unknown test result.';
        }

        if ($result === TestResult::FAILURE && trim($notes) === '') {
            return 'Please describe what went wrong, notes are required for a failed test: `/tested fail` followed by your notes.';
        }

        return null;
    }

    /**
     * Withdraw the result of a user for the head commit ("not tested" in the tracker).
     *
     * @param   IssueContext  $issue  The pull request.
     * @param   string        $login  The GitHub login of the tester.
     *
     * @return  TestOutcome
     *
     * @since   1.0
     */
    public function withdraw(IssueContext $issue, string $login): TestOutcome
    {
        if ($issue->headSha !== null && $existing = $this->repository->findByUser($issue->id, $login, $issue->headSha)) {
            $this->repository->delete((int) $existing->id);
            $this->refresh($issue);
        }

        return TestOutcome::accepted();
    }

    /**
     * Reset the test results of one user or of all users (maintainers only).
     *
     * @param   IssueContext  $issue   The pull request.
     * @param   string        $actor   The GitHub login of the user resetting the results.
     * @param   string|null   $target  The user whose results are reset, null to reset all results.
     *
     * @return  TestOutcome
     *
     * @since   1.0
     */
    public function reset(IssueContext $issue, string $actor, ?string $target = null): TestOutcome
    {
        if (!$this->isMaintainer($actor)) {
            return TestOutcome::rejected('Only maintainers with write access to the repository can reset test results.');
        }

        $count = $this->repository->deleteForItem($issue->id, $target);

        $this->logger->info(
            \sprintf(
                '%1$s reset %2$d test results of %3$s for %4$s/%5$s #%6$d',
                $actor,
                $count,
                $target ?? 'all users',
                $this->project->gh_user,
                $this->project->gh_project,
                $issue->number
            )
        );

        $this->refresh($issue);

        return TestOutcome::accepted();
    }

    /**
     * Remove the result recorded from a comment.
     *
     * @param   IssueContext  $issue      The pull request.
     * @param   integer       $commentId  The GitHub comment ID.
     *
     * @return  boolean  True if a result has been removed.
     *
     * @since   1.0
     */
    public function removeByComment(IssueContext $issue, int $commentId): bool
    {
        $existing = $this->repository->findByCommentId($commentId);

        if (!$existing || $existing->itemId !== $issue->id) {
            return false;
        }

        $this->repository->delete((int) $existing->id);

        if ($issue->open) {
            $this->refresh($issue);
        }

        return true;
    }

    /**
     * Process an update of the commits of a pull request (opened, reopened, synchronize).
     *
     * Calculates the diff fingerprint, moves the results with an unchanged diff to the new head commit and updates GitHub.
     *
     * @param   IssueContext  $issue  The pull request with the new head commit.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function pullRequestUpdated(IssueContext $issue): void
    {
        if (!$issue->hasCode || $issue->headSha === null) {
            return;
        }

        $this->updateDiffHash($issue);

        if ($issue->diffHash !== null) {
            $moved = $this->repository->carryOver($issue->id, $issue->diffHash, $issue->headSha);

            if ($moved) {
                $this->logger->info(
                    \sprintf(
                        'Kept %1$d test results for %2$s/%3$s #%4$d, the diff did not change.',
                        $moved,
                        $this->project->gh_user,
                        $this->project->gh_project,
                        $issue->number
                    )
                );
            }
        }

        if ($issue->open) {
            $this->refresh($issue);
        }
    }

    /**
     * Prepare a pull request which has been open before the project switched to testing on GitHub.
     *
     * The results recorded for the head commit get the diff fingerprint, so they survive a later rebase.
     *
     * @param   IssueContext  $issue  The pull request.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function initialise(IssueContext $issue): void
    {
        if (!$issue->hasCode || $issue->headSha === null) {
            return;
        }

        $this->updateDiffHash($issue);

        if ($issue->diffHash !== null) {
            $this->repository->backfillDiffHash($issue->id, $issue->headSha, $issue->diffHash);
        }

        if ($issue->open) {
            $this->refresh($issue);
        }
    }

    /**
     * Synchronise the RTC label with the status of the issue in the tracker.
     *
     * With testing on GitHub the label is set by maintainers on GitHub and the tracker follows it.
     *
     * @param   IssueContext  $issue   The pull request.
     * @param   string        $action  The webhook action (labeled or unlabeled).
     * @param   string        $label   The name of the label.
     *
     * @return  boolean  True if the status has been changed.
     *
     * @since   1.0
     */
    public function labelChanged(IssueContext $issue, string $action, string $label): bool
    {
        if (strcasecmp($label, self::RTC_LABEL) !== 0) {
            return false;
        }

        if ($action === 'labeled' && $issue->open) {
            return $this->repository->setIssueStatus($issue->id, self::STATUS_RTC);
        }

        if ($action === 'unlabeled') {
            return $this->repository->setIssueStatus($issue->id, self::STATUS_PENDING, self::STATUS_RTC);
        }

        return false;
    }

    /**
     * Update the section in the pull request description, the commit status and the labels.
     *
     * @param   IssueContext  $issue  The pull request.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function refresh(IssueContext $issue): void
    {
        // Always work on the current pull request to keep the window for concurrent edits of the description small
        $pullRequest = $this->gateway->getPullRequest($issue->number);

        if (($pullRequest->state ?? 'open') !== 'open') {
            return;
        }

        $headSha = (string) ($pullRequest->head->sha ?? $issue->headSha);
        $summary = new TestSummary($this->repository->getResults($issue->id), $headSha);

        $this->updateDescription($issue, $pullRequest, $summary, $headSha);
        $this->updateStatus($issue, $summary, $headSha);
        $this->updateLabels($issue, $pullRequest, $summary);
    }

    /**
     * Update the section in the pull request description.
     *
     * @param   IssueContext  $issue        The pull request.
     * @param   object        $pullRequest  The pull request data from GitHub.
     * @param   TestSummary   $summary      The test results.
     * @param   string        $headSha      The head commit.
     *
     * @return  void
     *
     * @since   1.0
     */
    private function updateDescription(IssueContext $issue, object $pullRequest, TestSummary $summary, string $headSha): void
    {
        $repository = $this->project->gh_user . '/' . $this->project->gh_project;

        $section = $this->section->render(
            $summary,
            [
                'repository'     => $repository,
                'number'         => $issue->number,
                'author'         => $issue->author,
                'bot'            => $this->botName,
                'trackerUrl'     => $this->getTrackerItemUrl($issue),
                'environmentUrl' => $this->environment->getUrl($this->project, $issue->number, $headSha),
            ]
        );

        $body    = (string) ($pullRequest->body ?? '');
        $newBody = PrDescriptionSection::replace($body, $section);

        if ($this->normalise($newBody) === $this->normalise($body)) {
            return;
        }

        $this->gateway->updatePullRequestBody($issue->number, $newBody);
    }

    /**
     * Create the `JTracker/HumanTestResults` commit status for the head commit.
     *
     * @param   IssueContext  $issue    The pull request.
     * @param   TestSummary   $summary  The test results.
     * @param   string        $headSha  The head commit.
     *
     * @return  void
     *
     * @since   1.0
     */
    private function updateStatus(IssueContext $issue, TestSummary $summary, string $headSha): void
    {
        if ($headSha === '') {
            return;
        }

        $this->gateway->createStatus(
            $headSha,
            $summary->getState(),
            'https://github.com/' . $this->project->gh_user . '/' . $this->project->gh_project . '/pull/' . $issue->number,
            \sprintf('Human Test Results: %1$d Successful %2$d Failed.', \count($summary->getSuccessful()), \count($summary->getFailed())),
            self::STATUS_CONTEXT
        );
    }

    /**
     * Update the `Tested: …` labels.
     *
     * @param   IssueContext  $issue        The pull request.
     * @param   object        $pullRequest  The pull request data from GitHub.
     * @param   TestSummary   $summary      The test results.
     *
     * @return  void
     *
     * @since   1.0
     */
    private function updateLabels(IssueContext $issue, object $pullRequest, TestSummary $summary): void
    {
        $present = [];

        foreach ((array) ($pullRequest->labels ?? []) as $label) {
            $present[] = (string) ($label->name ?? '');
        }

        $wanted = $summary->getLabel();

        foreach ([TestSummary::LABEL_ONE, TestSummary::LABEL_ENOUGH] as $label) {
            if ($label !== $wanted && \in_array($label, $present, true)) {
                $this->gateway->removeLabel($issue->number, $label);
            }
        }

        if ($wanted !== null && !\in_array($wanted, $present, true)) {
            $this->gateway->addLabels($issue->number, [$wanted]);
        }
    }

    /**
     * React to the comment with the command.
     *
     * @param   IssueContext  $issue      The pull request.
     * @param   integer       $commentId  The comment ID.
     * @param   string        $login      The author of the comment.
     * @param   TestOutcome   $outcome    The outcome of the command.
     * @param   string        $action     The webhook action (created or edited).
     *
     * @return  void
     *
     * @since   1.0
     */
    private function giveFeedback(IssueContext $issue, int $commentId, string $login, TestOutcome $outcome, string $action): void
    {
        try {
            $this->gateway->addReaction($commentId, $outcome->accepted ? '+1' : 'confused');

            // Only explain a rejection once, not for every edit of the comment
            if (!$outcome->accepted && $action === 'created') {
                $this->gateway->createComment(
                    $issue->number,
                    \sprintf(
                        "@%1\$s %2\$s\r\n\r\n<sub>See \"How to test this pull request\" at the bottom of the pull request description.</sub>",
                        $login,
                        $outcome->message
                    )
                );
            }
        } catch (\Exception $exception) {
            // Feedback is nice to have, the result has been processed anyway
            $this->logger->warning('Could not give feedback on a test command', ['exception' => $exception]);
        }
    }

    /**
     * Check if a user is allowed to use maintainer commands.
     *
     * @param   string  $login  The GitHub login.
     *
     * @return  boolean
     *
     * @since   1.0
     */
    private function isMaintainer(string $login): bool
    {
        try {
            return \in_array($this->gateway->getPermission($login), self::MAINTAINER_PERMISSIONS, true);
        } catch (\Exception $exception) {
            $this->logger->warning('Could not check the repository permission of ' . $login, ['exception' => $exception]);

            return false;
        }
    }

    /**
     * Calculate the diff fingerprint if it is not known yet.
     *
     * @param   IssueContext  $issue  The pull request.
     *
     * @return  void
     *
     * @since   1.0
     */
    private function ensureDiffHash(IssueContext $issue): void
    {
        if ($issue->diffHash === null && $issue->headSha !== null) {
            $this->updateDiffHash($issue);
        }
    }

    /**
     * Calculate and store the diff fingerprint of the head commit.
     *
     * @param   IssueContext  $issue  The pull request.
     *
     * @return  void
     *
     * @since   1.0
     */
    private function updateDiffHash(IssueContext $issue): void
    {
        try {
            $issue->diffHash = $this->fingerprint->calculate($this->gateway->getPullRequestFiles($issue->number));
        } catch (\Exception $exception) {
            // Without a fingerprint results are not carried over, which resets them like before
            $this->logger->error(
                \sprintf('Could not calculate the diff fingerprint of %s/%s #%d', $this->project->gh_user, $this->project->gh_project, $issue->number),
                ['exception' => $exception]
            );

            $issue->diffHash = null;

            return;
        }

        $this->repository->setIssueDiffHash($issue->id, $issue->diffHash, (string) $issue->headSha);
    }

    /**
     * Get the URL of the pull request in the tracker.
     *
     * @param   IssueContext  $issue  The pull request.
     *
     * @return  string
     *
     * @since   1.0
     */
    private function getTrackerItemUrl(IssueContext $issue): string
    {
        if ($this->trackerUrl === '') {
            return '';
        }

        return rtrim($this->trackerUrl, '/') . '/tracker/' . $this->project->alias . '/' . $issue->number;
    }

    /**
     * Format a date for the database (UTC).
     *
     * @param   string|null  $date  The date, null for now.
     *
     * @return  string
     *
     * @since   1.0
     */
    private function formatDate(?string $date): string
    {
        try {
            $dateTime = new \DateTime($date ?? 'now');
        } catch (\Exception) {
            $dateTime = new \DateTime();
        }

        return $dateTime->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    }

    /**
     * Normalise the line endings of a text for comparisons.
     *
     * @param   string  $text  The text.
     *
     * @return  string
     *
     * @since   1.0
     */
    private function normalise(string $text): string
    {
        return rtrim(str_replace(["\r\n", "\r"], "\n", $text));
    }
}
