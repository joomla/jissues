<?php

/**
 * Part of the Joomla! Tracker Package.
 *
 * @copyright  Copyright (C) 2012 - 2026 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace JTracker\Tests\App\Tracker\Testing;

use App\Projects\TrackerProject;
use App\Tracker\Testing\GithubTestingGateway;
use App\Tracker\Testing\IssueContext;
use App\Tracker\Testing\NullTestEnvironmentProvider;
use App\Tracker\Testing\PrDescriptionSection;
use App\Tracker\Testing\TestResult;
use App\Tracker\Testing\TestResultRepository;
use App\Tracker\Testing\TestResultService;
use App\Tracker\Testing\TestSummary;
use Joomla\Database\DatabaseInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the rules of the test result service.
 *
 * The gateway is a mock because some tests check the GitHub calls; the other tests only use it as a stub.
 *
 * @since  1.0
 */
#[AllowMockObjectsWithoutExpectations]
class TestResultServiceTest extends TestCase
{
    /**
     * The in-memory repository.
     *
     * @var  InMemoryTestResultRepository
     */
    private $repository;

    /**
     * The mocked GitHub access.
     *
     * @var  GithubTestingGateway&MockObject
     */
    private $gateway;

    /**
     * The pull request as GitHub returns it.
     *
     * @var  object
     */
    private $pullRequest;

    /**
     * The files of the pull request as GitHub returns them.
     *
     * @var  object[]
     */
    private $files;

    /**
     * The service under test.
     *
     * @var  TestResultService
     */
    private $service;

    /**
     * Set up the service with an in-memory repository and a mocked gateway.
     *
     * @return  void
     */
    protected function setUp(): void
    {
        $db = $this->createStub(DatabaseInterface::class);

        $project = new TrackerProject(
            $db,
            [
                'project_id'      => 5,
                'title'           => 'Weblinks',
                'alias'           => 'weblinks',
                'gh_user'         => 'joomla-extensions',
                'gh_project'      => 'weblinks',
                'gh_editbot_user' => 'joomla-bot',
                'github_testing'  => 1,
            ]
        );

        $this->pullRequest = (object) [
            'state'  => 'open',
            'body'   => 'Test instructions of the author',
            'head'   => (object) ['sha' => 'head'],
            'labels' => [],
        ];

        $this->repository = new InMemoryTestResultRepository($db);
        $this->gateway    = $this->createMock(GithubTestingGateway::class);

        $this->gateway->method('getPullRequest')->willReturnCallback(fn () => $this->pullRequest);
        $this->files = [(object) ['filename' => 'a.php', 'status' => 'modified', 'patch' => "@@ -1 +1 @@\n-a\n+b"]];
        $this->gateway->method('getPullRequestFiles')->willReturnCallback(fn () => $this->files);

        $this->service = new TestResultService(
            $project,
            $this->repository,
            $this->gateway,
            new PrDescriptionSection(JPATH_ROOT . '/templates/tracker/github/test-instructions.md'),
            new NullTestEnvironmentProvider(),
            'joomla-bot'
        );
    }

    /**
     * Create the context of an open pull request.
     *
     * @param   boolean  $open  Whether the pull request is open.
     *
     * @return  IssueContext
     */
    private function issue(bool $open = true): IssueContext
    {
        return new IssueContext(1, 42, 'author', $open, true, 'head', null, ['first', 'head']);
    }

    /**
     * Create a comment webhook payload.
     *
     * @param   string   $body       The comment text.
     * @param   string   $login      The author of the comment.
     * @param   integer  $commentId  The comment ID.
     * @param   string   $action     The webhook action.
     *
     * @return  object
     */
    private function comment(string $body, string $login = 'tester', int $commentId = 100, string $action = 'created'): object
    {
        return (object) [
            'action'  => $action,
            'comment' => (object) [
                'id'         => $commentId,
                'body'       => $body,
                'created_at' => '2026-11-21T10:00:00Z',
                'user'       => (object) ['login' => $login, 'type' => 'User'],
            ],
        ];
    }

    /**
     * A successful test is recorded and published in the description, the status and the labels.
     *
     * @return  void
     */
    public function testSuccessfulTestIsRecordedAndPublished(): void
    {
        $this->gateway->expects($this->once())->method('updatePullRequestBody')
            ->with(42, $this->callback(static function (string $body) {
                return str_starts_with($body, "Test instructions of the author\r\n\r\n" . PrDescriptionSection::START)
                    && str_contains($body, '| [tester](https://github.com/tester) |');
            }));
        $this->gateway->expects($this->once())->method('createStatus')
            ->with('head', 'pending', 'https://github.com/joomla-extensions/weblinks/pull/42', 'Human Test Results: 1 Successful 0 Failed.', TestResultService::STATUS_CONTEXT);
        $this->gateway->expects($this->once())->method('addLabels')->with(42, [TestSummary::LABEL_ONE]);
        $this->gateway->expects($this->once())->method('addReaction')->with(100, '+1');
        $this->gateway->expects($this->never())->method('createComment');

        $outcome = $this->service->handleComment($this->comment("/tested ok\nWorks"), $this->issue());

        $this->assertTrue($outcome->accepted);
        $this->assertCount(1, $this->repository->rows);

        $row = reset($this->repository->rows);

        $this->assertSame('tester', $row->username);
        $this->assertSame(TestResult::SUCCESS, $row->result);
        $this->assertSame('head', $row->sha);
        $this->assertSame('Works', $row->comment);
        $this->assertSame(100, $row->ghCommentId);
        $this->assertNotNull($row->diffHash);
        $this->assertSame('2026-11-21 10:00:00', $row->createdDate);
        $this->assertSame([['test_item', 'tester', '1']], $this->repository->activities);
    }

    /**
     * The author can not test their own pull request.
     *
     * @return  void
     */
    public function testSelfTestIsRejected(): void
    {
        $this->gateway->expects($this->once())->method('addReaction')->with(100, 'confused');
        $this->gateway->expects($this->once())->method('createComment')
            ->with(42, $this->stringContains('can not test their own pull request'));
        $this->gateway->expects($this->never())->method('updatePullRequestBody');

        $outcome = $this->service->handleComment($this->comment('/tested ok', 'Author'), $this->issue());

        $this->assertFalse($outcome->accepted);
        $this->assertSame([], $this->repository->rows);
    }

    /**
     * A failed test needs notes.
     *
     * @return  void
     */
    public function testFailureWithoutNotesIsRejected(): void
    {
        $outcome = $this->service->handleComment($this->comment('/tested fail'), $this->issue());

        $this->assertFalse($outcome->accepted);
        $this->assertStringContainsString('notes are required', $outcome->message);
        $this->assertSame([], $this->repository->rows);
    }

    /**
     * Closed pull requests are not tested anymore.
     *
     * @return  void
     */
    public function testClosedPullRequestIsRejected(): void
    {
        $outcome = $this->service->handleComment($this->comment('/tested ok'), $this->issue(false));

        $this->assertFalse($outcome->accepted);
        $this->assertSame([], $this->repository->rows);
    }

    /**
     * Comments of the bot and comments without a command are ignored.
     *
     * @return  void
     */
    public function testIgnoredComments(): void
    {
        $this->gateway->expects($this->never())->method('addReaction');

        $this->assertNull($this->service->handleComment($this->comment('/tested ok', 'joomla-bot'), $this->issue()));
        $this->assertNull($this->service->handleComment($this->comment('Nice work!'), $this->issue()));
    }

    /**
     * A new result of the same user replaces the earlier one, repeating a result collects no points.
     *
     * @return  void
     */
    public function testNewResultReplacesEarlierResult(): void
    {
        $this->service->handleComment($this->comment('/tested ok', 'tester', 100), $this->issue());
        $this->service->handleComment($this->comment('/tested ok', 'tester', 101), $this->issue());
        $this->service->handleComment($this->comment("/tested fail\nBroken now", 'tester', 102), $this->issue());

        $this->assertCount(1, $this->repository->rows);

        $row = reset($this->repository->rows);

        $this->assertSame(TestResult::FAILURE, $row->result);
        $this->assertSame(102, $row->ghCommentId);
        $this->assertCount(2, $this->repository->activities);
    }

    /**
     * Processing the same comment twice (tracker submission and webhook) records it once.
     *
     * @return  void
     */
    public function testSameCommentIsRecordedOnce(): void
    {
        $this->service->record($this->issue(), 'tester', TestResult::SUCCESS, 'Notes', ['sha' => 'head'], 100, 'tracker', 'head');
        $this->service->handleComment($this->comment("/tested ok\nNotes", 'tester', 100), $this->issue());

        $this->assertCount(1, $this->repository->rows);
        $this->assertSame('tracker', reset($this->repository->rows)->source);
        $this->assertCount(1, $this->repository->activities);
    }

    /**
     * Editing a comment changes the result, removing the command or deleting the comment removes the result.
     *
     * @return  void
     */
    public function testEditAndDeleteComment(): void
    {
        $this->service->handleComment($this->comment('/tested ok'), $this->issue());
        $this->service->handleComment($this->comment("/tested fail\nOops", 'tester', 100, 'edited'), $this->issue());

        $this->assertSame(TestResult::FAILURE, reset($this->repository->rows)->result);

        $this->service->handleComment($this->comment('No result anymore', 'tester', 100, 'edited'), $this->issue());

        $this->assertSame([], $this->repository->rows);

        $this->service->handleComment($this->comment('/tested ok', 'tester', 101), $this->issue());
        $this->service->handleComment($this->comment('/tested ok', 'tester', 101, 'deleted'), $this->issue());

        $this->assertSame([], $this->repository->rows);
    }

    /**
     * Only maintainers can reset results.
     *
     * @return  void
     */
    public function testReset(): void
    {
        $this->gateway->method('getPermission')->willReturnMap([['maintainer', 'write'], ['tester2', 'read']]);

        $this->service->handleComment($this->comment('/tested ok', 'tester', 100), $this->issue());
        $this->service->handleComment($this->comment('/tested ok', 'tester2', 101), $this->issue());

        $outcome = $this->service->handleComment($this->comment('/tested reset', 'tester2', 102), $this->issue());

        $this->assertFalse($outcome->accepted);
        $this->assertCount(2, $this->repository->rows);

        $outcome = $this->service->handleComment($this->comment('/tested reset @tester', 'maintainer', 103), $this->issue());

        $this->assertTrue($outcome->accepted);
        $this->assertSame(['tester2'], array_values(array_map(static fn ($row) => $row->username, $this->repository->rows)));

        $this->service->handleComment($this->comment('/tested reset', 'maintainer', 104), $this->issue());

        $this->assertSame([], $this->repository->rows);
    }

    /**
     * Results survive a push which does not change the diff, and are outdated after a push which does.
     *
     * @return  void
     */
    public function testPullRequestUpdate(): void
    {
        $this->service->handleComment($this->comment('/tested ok'), $this->issue());

        // Rebase: new head, same diff
        $this->pullRequest->head->sha  = 'rebased';
        $issue                         = new IssueContext(1, 42, 'author', true, true, 'rebased', null, ['first', 'rebased']);

        $this->service->pullRequestUpdated($issue);

        $row = reset($this->repository->rows);

        $this->assertSame('rebased', $row->sha);
        $this->assertSame('head', $row->testedSha);
        $this->assertSame(['tester'], (new TestSummary($this->repository->getResults(1), 'rebased'))->getSuccessful());

        // Code change: new head, other diff
        $this->files                   = [(object) ['filename' => 'a.php', 'status' => 'modified', 'patch' => "@@ -1 +1 @@\n-a\n+c"]];
        $this->pullRequest->head->sha  = 'changed';
        $issue                         = new IssueContext(1, 42, 'author', true, true, 'changed', null, ['first', 'rebased', 'changed']);

        $this->service->pullRequestUpdated($issue);

        $summary = new TestSummary($this->repository->getResults(1), 'changed');

        $this->assertSame([], $summary->getSuccessful());
        $this->assertSame(['tester'], array_keys($summary->outdated));
    }

    /**
     * The RTC label sets the status of the issue.
     *
     * @return  void
     */
    public function testRtcLabel(): void
    {
        $this->assertTrue($this->service->labelChanged($this->issue(), 'labeled', 'RTC'));
        $this->assertSame([1, TestResultService::STATUS_RTC, null], $this->repository->statusChanges[0]);

        $this->assertTrue($this->service->labelChanged($this->issue(), 'unlabeled', 'RTC'));
        $this->assertSame([1, TestResultService::STATUS_PENDING, TestResultService::STATUS_RTC], $this->repository->statusChanges[1]);

        $this->assertFalse($this->service->labelChanged($this->issue(), 'labeled', 'Language Change'));
    }

    /**
     * Unchanged descriptions are not written again.
     *
     * @return  void
     */
    public function testUnchangedDescriptionIsNotWritten(): void
    {
        $this->service->handleComment($this->comment('/tested ok'), $this->issue());

        // GitHub now has the description written by the bot, with other line endings
        $this->pullRequest->body = str_replace("\r\n", "\n", $this->renderedBody());

        $this->gateway->expects($this->never())->method('updatePullRequestBody');

        $this->service->refresh($this->issue());
    }

    /**
     * Render the body the service would write for the current results.
     *
     * @return  string
     */
    private function renderedBody(): string
    {
        $section = (new PrDescriptionSection(JPATH_ROOT . '/templates/tracker/github/test-instructions.md'))->render(
            new TestSummary($this->repository->getResults(1), 'head'),
            [
                'repository'     => 'joomla-extensions/weblinks',
                'number'         => 42,
                'author'         => 'author',
                'bot'            => 'joomla-bot',
                'trackerUrl'     => 'https://issues.joomla.org/tracker/weblinks/42',
                'environmentUrl' => null,
            ]
        );

        return PrDescriptionSection::replace('Test instructions of the author', $section);
    }
}

/**
 * In-memory replacement of the repository.
 *
 * @since  1.0
 */
class InMemoryTestResultRepository extends TestResultRepository
{
    /**
     * The stored results.
     *
     * @var  TestResult[]
     */
    public array $rows = [];

    /**
     * The recorded activities as [event, user, text].
     *
     * @var  array
     */
    public array $activities = [];

    /**
     * The recorded status changes as [item, status, only if status].
     *
     * @var  array
     */
    public array $statusChanges = [];

    /**
     * The last ID.
     *
     * @var  integer
     */
    private int $lastId = 0;

    public function getResults(int $itemId): array
    {
        return array_values(array_map(static fn (TestResult $row) => clone $row, array_filter($this->rows, static fn (TestResult $row) => $row->itemId === $itemId)));
    }

    public function findByCommentId(int $commentId): ?TestResult
    {
        foreach ($this->rows as $row) {
            if ($row->ghCommentId === $commentId) {
                return clone $row;
            }
        }

        return null;
    }

    public function findByUser(int $itemId, string $username, string $sha): ?TestResult
    {
        foreach (array_reverse($this->rows) as $row) {
            if ($row->itemId === $itemId && $row->username === $username && $row->sha === $sha) {
                return clone $row;
            }
        }

        return null;
    }

    public function save(TestResult $result): TestResult
    {
        if (!$result->id) {
            $result->id = ++$this->lastId;
        }

        $this->rows[$result->id] = clone $result;

        return $result;
    }

    public function delete(int $id): void
    {
        unset($this->rows[$id]);
    }

    public function deleteForItem(int $itemId, ?string $username = null): int
    {
        $count = 0;

        foreach ($this->rows as $id => $row) {
            if ($row->itemId === $itemId && ($username === null || $row->username === $username)) {
                unset($this->rows[$id]);
                $count++;
            }
        }

        return $count;
    }

    public function carryOver(int $itemId, string $diffHash, string $headSha): int
    {
        $count = 0;

        foreach ($this->rows as $row) {
            if ($row->itemId === $itemId && $row->diffHash === $diffHash && $row->sha !== $headSha) {
                $row->testedSha = $row->testedSha ?? $row->sha;
                $row->sha       = $headSha;
                $count++;
            }
        }

        return $count;
    }

    public function backfillDiffHash(int $itemId, string $headSha, string $diffHash): int
    {
        return 0;
    }

    public function setIssueDiffHash(int $itemId, string $diffHash, string $headSha): void
    {
    }

    public function setIssueStatus(int $itemId, int $status, ?int $onlyIfStatus = null): bool
    {
        $this->statusChanges[] = [$itemId, $status, $onlyIfStatus];

        return true;
    }

    public function addActivity(string $event, string $userName, int $projectId, int $issueNumber, string $text, ?string $dateTime = null): void
    {
        $this->activities[] = [$event, $userName, $text];
    }
}
