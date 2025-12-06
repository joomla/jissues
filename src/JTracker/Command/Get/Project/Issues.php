<?php

/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Command\Get\Project;

use App\Projects\Table\LabelsTable;
use App\Projects\Table\MilestonesTable;
use App\Tracker\Table\IssuesTable;
use App\Tracker\Table\StatusTable;
use Joomla\Date\Date;
use JTracker\Command\Get\Project;
use JTracker\Github\GithubFactory;
use JTracker\Helper\GitHubHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class for retrieving issues from GitHub for selected projects
 *
 * @since  1.0
 */
class Issues extends Project
{
    /**
     * The command name - available to be used as a reference for searching.
     *
     * @var    string
     * @since  2.0.0
     */
    public const COMMAND_NAME = 'get:project_issues';

    /**
     * List of changed issue numbers.
     *
     * @var array
     *
     * @since  1.0
     */
    protected $changedIssueNumbers = [];

    /**
     * List of issues.
     *
     * @var array
     *
     * @since  1.0
     */
    protected $issues = [];

    /**
     * Status of issues.
     *
     * @var array
     *
     * @since  1.0
     */
    protected $issueStates = ['open', 'closed'];

    /**
     * Configure the command.
     *
     * @return  void
     *
     * @since   2.0.0
     */
    protected function configure(): void
    {
        parent::configure();

        $this->setName(self::COMMAND_NAME);
        $this->setDescription('Retrieve issue from GitHub.');
    }

    /**
     * Execute the command.
     *
     * @param   InputInterface   $input   The input to inject into the command.
     * @param   OutputInterface  $output  The output to inject into the command.
     *
     * @return  integer
     *
     * @since   1.0
     */
    protected function doExecute(InputInterface $input, OutputInterface $output): int
    {
        $ioStyle = new SymfonyStyle($input, $output);
        $ioStyle->title('Retrieve Issues');

        $this->logOut('Start retrieve Issues')
            ->selectProject($input, $ioStyle)
            ->setupGitHub()
            ->selectType($input, $ioStyle)
            ->fetchData($ioStyle)
            ->processData($ioStyle);
        $ioStyle->newLine();
        $this->logOut('Finished.');

        return Command::SUCCESS;
    }

    /**
     * Select the status of issues to process.
     *
     * @param   InputInterface  $input  The input to inject into the command.
     * @param   SymfonyStyle    $io     The output decorator
     *
     * @return  $this
     *
     * @since   1.0
     */
    protected function selectType(InputInterface $input, SymfonyStyle $io)
    {
        // Get status option
        $status = $input->getOption('status');

        // Process all the status - do nothing
        if ($status == 'all') {
            return $this;
        }

        // When status option is open or closed process it directly.
        if ($status == 'open' || $status == 'closed') {
            $this->issueStates = [$status];

            return $this;
        }

        // Select what to process
        $question = new ChoiceQuestion(
            'Select GitHub issues status? 1) All, 2) Open, 3) Closed',
            ['1', '2']
        );

        $resp = $io->askQuestion($question);

        if ((int) $resp == 2) {
            $this->issueStates = ['open'];
        } elseif ((int) $resp == 3) {
            $this->issueStates = ['closed'];
        }

        return $this;
    }

    /**
     * Method to pull the list of issues from GitHub
     *
     * @param   SymfonyStyle  $io  The output decorator
     *
     * @return  $this
     *
     * @since   1.0
     */
    protected function fetchData(SymfonyStyle $io)
    {
        $issues = [];

        foreach ($this->issueStates as $state) {
            $io->write(\sprintf('Retrieving <b>%s</b> items from GitHub...', $state));

            if ($io->isVerbose()) {
                $io->write('For: ' . $this->project->gh_user . '/' . $this->project->gh_project);
            }

            $page = 0;

            do {
                $page++;
                $issues_more = $this->github->issues->getListByRepository(
                    // Owner
                    $this->project->gh_user,
                    // Repository
                    $this->project->gh_project,
                    // Milestone
                    null,
                    // State [ open | closed ]
                    $state,
                    // Assignee
                    null,
                    // Creator
                    null,
                    // Labels
                    null,
                    // Sort
                    'created',
                    // Direction
                    'asc',
                    // Since
                    null,
                    // Page
                    $page,
                    // Count
                    100
                );

                $this->checkGitHubRateLimit($this->github->issues->getRateLimitRemaining());

                $count = \is_array($issues_more) ? \count($issues_more) : 0;

                if ($count) {
                    $issues = array_merge($issues, $issues_more);

                    $io->write('(' . $count . ')');
                }
            } while ($count);

            $io->newLine();
        }

        usort(
            $issues,
            function ($first, $second) {
                return $first->number - $second->number;
            }
        );

        $this->logOut(
            \sprintf(
                'Retrieved <b>%d</b> items from GitHub.',
                \count($issues)
            )
        );

        $this->issues = $issues;

        return $this;
    }

    /**
     * Method to process the list of issues and inject into the database as needed
     *
     * @param   SymfonyStyle  $io  The output decorator
     *
     * @return  $this
     *
     * @since   1.0
     * @throws  \RuntimeException
     */
    protected function processData(SymfonyStyle $io)
    {
        $ghIssues = $this->issues;
        $dbIssues = $this->getDbIssues();

        if (!$ghIssues) {
            throw new \UnexpectedValueException('No issues received...');
        }

        $added   = 0;
        $updated = 0;

        $milestones = $this->getMilestones();

        $io->write('Adding issues to the database...');

        $this->usePBar ? $io->progressStart(\count($ghIssues)) : null;

        // Start processing the pulls now
        foreach ($ghIssues as $count => $ghIssue) {
            $this->usePBar
                ? $io->progressAdvance()
                : $io->write($ghIssue->number . '...', false);

            if (!$this->checkInRange($ghIssue->number)) {
                // Not in range
                $this->usePBar ? null : $io->write('NiR ', false);

                continue;
            }

            $id = 0;

            foreach ($dbIssues as $dbIssue) {
                if ($ghIssue->number == $dbIssue->issue_number) {
                    if ($this->force) {
                        // Force update
                        $this->usePBar ? null : $io->write('F ', false);
                        $id = $dbIssue->id;

                        break;
                    }

                    $d1 = new Date($ghIssue->updated_at);
                    $d2 = new Date($dbIssue->modified_date);

                    if ($d1 == $d2) {
                        // No update required
                        $this->usePBar ? null : $io->write('- ', false);

                        continue 2;
                    }

                    $id = $dbIssue->id;

                    break;
                }
            }

            // Store the item in the database
            $table = new IssuesTable($this->getContainer()->get('db'));

            if ($id) {
                $table->load($id);
            }

            $table->issue_number = $ghIssue->number;
            $table->title        = $ghIssue->title;

            if ($table->description_raw != $ghIssue->body) {
                $table->description = $this->github->markdown->render(
                    $ghIssue->body,
                    'gfm',
                    $this->project->gh_user . '/' . $this->project->gh_project
                );

                $this->checkGitHubRateLimit($this->github->markdown->getRateLimitRemaining());

                $table->description_raw = $ghIssue->body;
            }

            $statusTable = new StatusTable($this->getContainer()->get('db'));

            // Get the list of status IDs based on the GitHub issue state
            $state = ($ghIssue->state == 'open') ? false : true;

            $stateIds = $statusTable->getStateStatusIds($state);

            // Check if the issue status is in the array; if it is, then the item didn't change open state and we don't need to change the status
            if (!\in_array($table->status, $stateIds)) {
                $table->status = $state ? 10 : 1;
            }

            $table->opened_date = (new Date($ghIssue->created_at))->format('Y-m-d H:i:s');
            $table->opened_by   = $ghIssue->user->login;

            $table->modified_date = (new Date($ghIssue->updated_at))->format('Y-m-d H:i:s');
            $table->modified_by   = $ghIssue->user->login;

            $table->project_id   = $this->project->project_id;
            $table->milestone_id = ($ghIssue->milestone && isset($milestones[$ghIssue->milestone->number]))
                ? $milestones[$ghIssue->milestone->number]
                : null;

            // We do not have a data about the default branch
            // @todo We need to retrieve repository somehow
            $table->build = 'master';

            // If the issue has a diff URL, it is a pull request.
            if (isset($ghIssue->pull_request->diff_url)) {
                $gitHubHelper = new GitHubHelper(GithubFactory::getInstance($this->getApplication()));

                $table->has_code = 1;

                // Get the pull request corresponding to an issue.
                if ($io->isVerbose()) {
                    $io->text('Get PR for the issue');
                }

                $pullRequest = $this->github->pulls->get(
                    $this->project->gh_user,
                    $this->project->gh_project,
                    $ghIssue->number
                );

                $table->build = $pullRequest->base->ref;

                // If the $pullRequest->head->user object is not set, the repo/branch had been deleted by the user.
                $table->pr_head_user = (isset($pullRequest->head->user))
                    ? $pullRequest->head->user->login
                    : 'unknown_repository';

                $table->pr_head_ref = $pullRequest->head->ref;
                $table->pr_head_sha = $pullRequest->head->sha;

                $combinedStatus = $gitHubHelper->getCombinedStatus($this->project, $pullRequest->head->sha);

                // Save the merge status to database
                $table->merge_state     = $combinedStatus->state;
                $table->gh_merge_status = json_encode($combinedStatus->statuses);

                // Get commits
                $commits = $gitHubHelper->getCommits($this->project, $table->issue_number);

                $table->commits = json_encode($commits);
            }

            // Add the closed date if the status is closed
            if ($ghIssue->closed_at) {
                $table->closed_date = (new Date($ghIssue->closed_at))->format('Y-m-d H:i:s');
            }

            // If the title has a [# in it, assume it's a JoomlaCode Tracker ID
            if (preg_match('/\[#([0-9]+)\]/', $ghIssue->title, $matches)) {
                $table->foreign_number = $matches[1];
            } elseif (preg_match('/tracker_item_id=([0-9]+)/', $ghIssue->body, $matches)) {
                // If the body has tracker_item_id= in it, that is a JoomlaCode Tracker ID
                $table->foreign_number = $matches[1];
            }

            $table->labels = implode(',', $this->getLabelIds($ghIssue->labels));

            $table->check()
                ->store(true);

            if (!$table->id) {
                // Bad coder :( - @todo when does this happen ??
                throw new \RuntimeException(
                    \sprintf(
                        'Invalid issue id for issue: %1$d in project id %2$s',
                        $ghIssue->number,
                        $this->project->project_id
                    )
                );
            }

            /*
            @todo see issue #194
            Add an open record to the activity table
            $activity               = new ActivitiesTable($db);
            $activity->project_id   = $this->project->project_id;
            $activity->issue_number = (int) $table->issue_number;
            $activity->user         = $issue->user->login;
            $activity->event        = 'open';
            $activity->created_date = $table->opened_date;

            $activity->store();

            / Add a close record to the activity table if the status is closed
            if ($issue->closed_at)
            {
                $activity               = new ActivitiesTable($db);
                $activity->project_id   = $this->project->project_id;
                $activity->issue_number = (int) $table->issue_number;
                $activity->event        = 'close';
                $activity->created_date = $issue->closed_at;

                $activity->store();
            }
            */

            // Store was successful, update status
            if ($id) {
                $updated++;
            } else {
                $added++;
            }

            $this->changedIssueNumbers[] = $ghIssue->number;
        }

        // Output the final result
        $io->progressFinish();
        $this->logOut(\sprintf('<ok>%1$d added, %2$d updated.</ok>', $added, $updated));

        return $this;
    }

    /**
     * Get a set of ids from label names.
     *
     * @param   array  $labelObjects  Array of label objects
     *
     * @return  array
     *
     * @since   1.0
     */
    private function getLabelIds($labelObjects)
    {
        static $labels = [];

        if (!$labels) {
            /** @var \Joomla\Database\DatabaseDriver $db */
            $db = $this->getContainer()->get('db');

            $table = new LabelsTable($db);

            $labelList = $db ->setQuery(
                $db->getQuery(true)
                    ->from($db->quoteName($table->getTableName()))
                    ->select(['label_id', 'name'])
                    ->where($db->quoteName('project_id') . ' = ' . $this->project->project_id)
            )->loadObjectList();

            foreach ($labelList as $labelObject) {
                $labels[$labelObject->name] = $labelObject->label_id;
            }
        }

        $ids = [];

        foreach ($labelObjects as $label) {
            if (!\array_key_exists($label->name, $labels)) {
                // @todo Label does not exist :( - reload labels for the project
            } else {
                $ids[] = $labels[$label->name];
            }
        }

        return $ids;
    }

    /**
     * Get the milestones for the active project.
     *
     * @return  array  An associative array of the milestone id's keyed by the Github milestone number.
     *
     * @since   1.0
     */
    private function getMilestones()
    {
        /** @var \Joomla\Database\DatabaseDriver $db */
        $db    = $this->getContainer()->get('db');
        $table = new MilestonesTable($db);

        $milestoneList = $db->setQuery(
            $db->getQuery(true)
                ->from($db->quoteName($table->getTableName()))
                ->select(['milestone_number', 'milestone_id'])
                ->where($db->quoteName('project_id') . ' = ' . $this->project->project_id)
        )->loadAssocList('milestone_number', 'milestone_id');

        return $milestoneList;
    }

    /**
     * Get an array of changed issue numbers.
     *
     * @return  array
     *
     * @since   1.0
     */
    public function getChangedIssueNumbers()
    {
        return $this->changedIssueNumbers;
    }

    /**
     * Method to get the GitHub issues from the database
     *
     * @return  $this
     *
     * @since   1.0
     */
    protected function getDbIssues()
    {
        /** @var \Joomla\Database\DatabaseDriver $db */
        $db = $this->getContainer()->get('db');

        $query = $db->getQuery(true);

        $query
            ->select($db->quoteName('id'))
            ->select($db->quoteName('issue_number'))
            ->select($db->quoteName('modified_date'))
            ->from($db->quoteName('#__issues'))
            ->where($db->quoteName('project_id') . '=' . (int) $this->project->project_id);

        // Issues range selected?
        if ($this->rangeTo != 0 && $this->rangeTo >= $this->rangeFrom) {
            $query->where($db->quoteName('issue_number') . ' >= ' . (int) $this->rangeFrom);
            $query->where($db->quoteName('issue_number') . ' <= ' . (int) $this->rangeTo);
        }

        $db->setQuery($query);

        return $db->loadObjectList();
    }
}
