<?php

/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Command\Get\Project;

use App\Projects\TrackerProject;
use App\Tracker\Table\ActivitiesTable;
use JTracker\Command\Get\Project;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class for retrieving events from GitHub for selected projects
 *
 * @since  1.0
 */
class Events extends Project
{
    /**
     * The command name - available to be used as a reference for searching.
     *
     * @var    string
     * @since  2.0.0
     */
    public const COMMAND_NAME = 'get:project_events';

    /**
     * Event data from GitHub
     *
     * @var    array
     * @since  1.0
     */
    protected $items = [];

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
        $this->setDescription('Retrieve issue events from GitHub.');
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
        $ioStyle->title('Retrieve Events');

        $this->logOut('Start retrieve Events')
            ->selectProject($input, $ioStyle)
            ->setupGitHub()
            ->fetchData($ioStyle)
            ->processData($ioStyle)
            ->logOut('Finished.');

        return Command::SUCCESS;
    }

    /**
     * Set the changed issues.
     *
     * @param   array  $changedIssueNumbers  List of changed issue numbers.
     *
     * @return $this
     *
     * @since   1.0
     */
    public function setChangedIssueNumbers(array $changedIssueNumbers)
    {
        $this->changedIssueNumbers = $changedIssueNumbers;

        return $this;
    }

    /**
     * Method to get the comments on items from GitHub
     *
     * @param   SymfonyStyle  $io  The output decorator
     *
     * @return  $this
     *
     * @since   1.0
     */
    protected function fetchData(SymfonyStyle $io)
    {
        if (!$this->changedIssueNumbers) {
            return $this;
        }

        $io->text(
            \sprintf(
                'Fetch events for <b>%d</b> issues from GitHub...',
                \count($this->changedIssueNumbers)
            )
        );

        $this->usePBar ? $io->progressStart(\count($this->changedIssueNumbers)) : null;

        foreach ($this->changedIssueNumbers as $count => $issueNumber) {
            $this->usePBar
                ? $io->progressAdvance()
                : $io->write(
                    \sprintf(
                        '%d/%d - # %d: ',
                        $count + 1,
                        \count($this->changedIssueNumbers),
                        $issueNumber
                    )
                );

            $page                      = 0;
            $this->items[$issueNumber] = [];

            do {
                $page++;

                $events = $this->github->issues->events->getList(
                    $this->project->gh_user,
                    $this->project->gh_project,
                    $issueNumber,
                    $page,
                    100
                );

                $this->checkGitHubRateLimit($this->github->issues->events->getRateLimitRemaining());

                $count = \is_array($events) ? \count($events) : 0;

                if ($count) {
                    $this->items[$issueNumber] = array_merge($this->items[$issueNumber], $events);

                    $this->usePBar
                            ? null
                            : $io->write($count . ' ');
                }
            } while ($count);
        }

        // Retrieved items, report status
        $io->progressFinish();
        $io->success('OK');

        return $this;
    }

    /**
     * Method to process the list of issues and inject into the database as needed
     *
     * @param   SymfonyStyle  $io  The output decorator
     *
     * @return  $this
     *
     * @throws  \UnexpectedValueException
     * @since   1.0
     */
    protected function processData(SymfonyStyle $io)
    {
        if (!$this->items) {
            $this->logOut('Everything is up to date.');

            return $this;
        }

        /** @var \Joomla\Database\DatabaseDriver $db */
        $db = $this->getContainer()->get('db');

        $query = $db->getQuery(true);

        $this->out('Adding events to the database...', false);

        $this->usePBar ? $io->progressStart(\count($this->items)) : null;

        $adds  = 0;
        $count = 0;

        // Initialize our ActivitiesTable instance to insert the new record
        $table = new ActivitiesTable($db);

        foreach ($this->items as $issueNumber => $events) {
            $this->usePBar
                ? null
                : $io->write(\sprintf(' #%d (%d/%d)...', $issueNumber, $count + 1, \count($this->items)));

            foreach ($events as $event) {
                switch ($event->event) {
                    case 'referenced':
                    case 'closed':
                    case 'reopened':
                    case 'assigned':
                    case 'unassigned':
                    case 'merged':
                    case 'head_ref_deleted':
                    case 'head_ref_restored':
                    case 'milestoned':
                    case 'demilestoned':
                    case 'renamed':
                    case 'locked':
                    case 'unlocked':
                        $query->clear()
                            ->select($table->getKeyName())
                            ->from($db->quoteName('#__activities'))
                            ->where($db->quoteName('gh_comment_id') . ' = ' . (int) $event->id)
                            ->where($db->quoteName('project_id') . ' = ' . (int) $this->project->project_id);

                        $db->setQuery($query);

                        $id = (int) $db->loadResult();

                        $table->reset();
                        $table->{$table->getKeyName()} = null;

                        if ($id && !$this->force) {
                            if ($this->force) {
                                // Force update
                                $this->usePBar ? null : $io->write('F', false);

                                $table->{$table->getKeyName()} = $id;
                            } else {
                                // If we have something already, then move on to the next item
                                $this->usePBar ? null : $io->write('-', false);

                                break;
                            }
                        } else {
                            $this->usePBar ? null : $io->write('+', false);
                        }

                        // Translate GitHub event names to "our" name schema
                        $evTrans = [
                            'referenced'       => 'reference', 'closed' => 'close', 'reopened' => 'reopen',
                            'assigned'         => 'assigned', 'unassigned' => 'unassigned', 'merged' => 'merge',
                            'head_ref_deleted' => 'head_ref_deleted', 'head_ref_restored' => 'head_ref_restored',
                            'milestoned'       => 'change', 'demilestoned' => 'change', 'labeled' => 'change', 'unlabeled' => 'change',
                            'renamed'          => 'change', 'locked' => 'locked', 'unlocked' => 'unlocked',
                        ];

                        $table->gh_comment_id = $event->id;
                        $table->issue_number  = $issueNumber;
                        $table->project_id    = $this->project->project_id;
                        $table->user          = $event->actor->login;
                        $table->event         = $evTrans[$event->event];
                        $table->created_date  = (new \DateTime($event->created_at))->format($db->getDateFormat());

                        if ($event->event == 'referenced') {
                            $table->text_raw = $event->commit_id;
                            $table->text     = $table->text_raw;
                        }

                        if ($event->event == 'assigned') {
                            $table->text_raw = 'Assigned to ' . $event->assignee->login;
                            $table->text     = $table->text_raw;
                        }

                        if ($event->event == 'unassigned') {
                            $table->text_raw = $event->assignee->login . ' was unassigned';
                            $table->text     = $table->text_raw;
                        }

                        if ($event->event == 'locked') {
                            $table->text_raw = $event->actor->login . ' locked the issue';
                            $table->text     = $table->text_raw;
                        }

                        if ($event->event == 'unlocked') {
                            $table->text_raw = $event->actor->login . ' unlocked the issue';
                            $table->text     = $table->text_raw;
                        }

                        $changes = $this->prepareChanges($event);

                        if (!empty($changes)) {
                            $table->text = json_encode($changes);
                        }

                        $table->store();

                        $adds++;

                        break;

                    case 'mentioned':
                    case 'subscribed':
                    case 'unsubscribed':
                    case 'labeled':
                    case 'unlabeled':
                        break;

                    default:
                        $this->logOut(\sprintf('ERROR: Unknown Event: %s', $event->event));

                        break;
                }
            }

            $count++;

            $this->usePBar
                ? $io->progressAdvance()
                : null;
        }

        $io->progressFinish();
        $io->success('OK');
        $this->logOut(\sprintf('Added %d new issue events to the database', $adds));

        return $this;
    }

    /**
     * Method to prepare the changes for saving.
     *
     * @param   object  $event  The issue event
     *
     * @return  array  The array of changes for activities list
     *
     * @since   1.0
     */
    private function prepareChanges($event)
    {
        /** @var \Joomla\Database\DatabaseDriver $db */
        $db = $this->getContainer()->get('db');

        $changes = [];

        switch ($event->event) {
            case 'milestoned':
                $milestoneId = null;

                $milestones = (new TrackerProject($db, $this->project))
                    ->getMilestones();

                // Get the id of added milestone
                foreach ($milestones as $milestone) {
                    if ($event->milestone->title == $milestone->title) {
                        $milestoneId = $milestone->milestone_id;
                    }
                }

                $change = new \stdClass();

                $change->name = 'milestone_id';
                $change->old  = null;
                $change->new  = $milestoneId;

                break;

            case 'demilestoned':
                $milestoneId = null;

                $milestones = (new TrackerProject($db, $this->project))
                    ->getMilestones();

                // Get the id of removed milestone
                foreach ($milestones as $milestone) {
                    if ($event->milestone->title == $milestone->title) {
                        $milestoneId = $milestone->milestone_id;
                    }
                }

                $change = new \stdClass();

                $change->name = 'milestone_id';
                $change->old  = $milestoneId;
                $change->new  = null;

                break;

            case 'renamed':
                $change = new \stdClass();

                $change->name = 'title';
                $change->old  = $event->rename->from;
                $change->new  = $event->rename->to;

                break;

            default:
                $change = null;
        }

        if ($change !== null) {
            $changes[] = $change;
        }

        return $changes;
    }
}
