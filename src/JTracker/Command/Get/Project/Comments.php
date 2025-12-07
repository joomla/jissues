<?php

/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Command\Get\Project;

use App\Tracker\Table\ActivitiesTable;
use JTracker\Command\Get\Project;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class for retrieving comments from GitHub for selected projects
 *
 * @since  1.0
 */
class Comments extends Project
{
    /**
     * The command name - available to be used as a reference for searching.
     *
     * @var    string
     * @since  2.0.0
     */
    public const COMMAND_NAME = 'get:project_comments';

    /**
     * Comment data from GitHub
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
        $this->setDescription('Retrieve comments from GitHub.');
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
        $ioStyle->title('Retrieve Comments');

        $this->logOut('Start retrieve Comments')
            ->selectProject($input, $ioStyle)
            ->setupGitHub()
            ->fetchData($ioStyle)
            ->processData($ioStyle);
        $ioStyle->newLine();
        $this->logOut('Finished.');

        return 0;
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
     * @param   SymfonyStyle  $io  The output object for rendering text.
     *
     * @return  $this
     *
     * @since   1.0
     */
    protected function fetchData(SymfonyStyle $io)
    {
        if (!\count($this->changedIssueNumbers)) {
            return $this;
        }

        $io->text(
            \sprintf(
                'Fetching comments for <b>%d</b> modified issues from GitHub...',
                \count($this->changedIssueNumbers)
            )
        );

        $this->usePBar ? $io->progressStart(\count($this->changedIssueNumbers)) : null;

        foreach ($this->changedIssueNumbers as $count => $issueNumber) {
            $this->usePBar
                ? $io->progressAdvance()
                : $io->text(
                    \sprintf(
                        '#%d (%d/%d):',
                        $issueNumber,
                        $count,
                        \count($this->changedIssueNumbers)
                    )
                );

            $page = 0;

            $this->items[$issueNumber] = [];

            do {
                $page++;

                $comments = $this->github->issues->comments->getList(
                    $this->project->gh_user,
                    $this->project->gh_project,
                    $issueNumber,
                    $page,
                    100
                );

                $this->checkGitHubRateLimit($this->github->issues->comments->getRateLimitRemaining());

                $count = \is_array($comments) ? \count($comments) : 0;

                if ($count) {
                    $this->items[$issueNumber] = array_merge($this->items[$issueNumber], $comments);
                }

                $this->usePBar
                    ? null
                    : $io->text($count . ' ');
            } while ($count);
        }

        $io->progressFinish();
        $io->newLine();
        $io->success('Finished');

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
     */
    protected function processData(SymfonyStyle $io)
    {
        if (!$this->items) {
            $this->logOut('Everything is up to date.');

            return $this;
        }

        /** @var \Joomla\Database\DatabaseDriver $db */
        $db = $this->getContainer()->get('db');

        // Initialize our query object
        $query = $db->getQuery(true);

        $io->text(
            \sprintf(
                'Processing comments for %d modified issues...',
                \count($this->items)
            )
        );

        $adds    = 0;
        $updates = 0;

        $count = 1;

        // Initialize our ActivitiesTable instance to insert the new record
        $table = new ActivitiesTable($db);

        // Comments ids for computing the difference
        $commentsIds = [];

        // Comments ids to delete
        $toDelete = [];

        // Start processing the comments now
        foreach ($this->items as $issueNumber => $comments) {
            if (!\count($comments)) {
                $io->newLine();
                $io->text(\sprintf('No comments for issue # %d', $issueNumber));
            } else {
                $io->newLine();
                $io->text(
                    \sprintf(
                        'Processing %1$d comments for issue # %2$d (%3$d/%4$d)',
                        \count($comments),
                        $issueNumber,
                        $count,
                        \count($this->items)
                    )
                );

                $this->usePBar ? $io->progressStart(\count($comments)) : null;

                foreach ($comments as $i => $comment) {
                    // Store the comment id for computing the difference
                    $commentsIds[] = $comment->id;

                    $check = $db->setQuery(
                        $query
                            ->clear()
                            ->select($table->getKeyName())
                            ->select($db->quoteName('updated_date'))
                            ->from($db->quoteName($table->getTableName()))
                            ->where($db->quoteName('gh_comment_id') . ' = ' . (int) $comment->id)
                            ->where($db->quoteName('project_id') . ' = ' . (int) $this->project->project_id)
                    )
                        ->loadObject();

                    if ($check) {
                        if (!$this->force) {
                            // If we have something already, check if it needs an update...
                            $d1 = new \DateTime($check->updated_date);
                            $d2 = new \DateTime($comment->updated_at);

                            if ($d1 == $d2) {
                                // No update required
                                $this->usePBar
                                    ? $io->progressAdvance()
                                    : $io->write('-');

                                continue;
                            }
                        }

                        $table->load($check->{$table->getKeyName()});

                        $this->usePBar ? null : $io->write(($this->force ? 'F ' : '~ '));
                    } else {
                        // New item
                        $table->reset();
                        $table->{$table->getKeyName()} = null;

                        $this->usePBar ? null : $this->out('+', false);
                    }

                    $table->gh_comment_id = $comment->id;
                    $table->issue_number  = (int) $issueNumber;
                    $table->project_id    = $this->project->project_id;
                    $table->user          = $comment->user->login;
                    $table->event         = 'comment';
                    $table->text_raw      = $comment->body;

                    $table->text = $this->github->markdown->render(
                        $comment->body,
                        'gfm',
                        $this->project->gh_user . '/' . $this->project->gh_project
                    );

                    $this->checkGitHubRateLimit($this->github->markdown->getRateLimitRemaining());

                    $table->created_date = (new \DateTime($comment->created_at))->format($db->getDateFormat());
                    $table->updated_date = (new \DateTime($comment->updated_at))->format($db->getDateFormat());

                    $table->store();

                    if ($check) {
                        $updates++;
                    } else {
                        $adds++;
                    }

                    $this->usePBar
                        ? $io->progressAdvance()
                        : null;
                }

                $io->progressFinish();
                $count++;
            }

            // Compute the difference between GitHub comments and issue comments
            $issueComments    = $this->getIssueCommentsIds($issueNumber);
            $commentsToDelete = array_diff($issueComments, $commentsIds);

            $toDelete = array_merge($toDelete, $commentsToDelete);
        }

        // Delete comments which does not exist on GitHub
        if (!empty($toDelete)) {
            $this->deleteIssuesComments($toDelete);
        }

        $io->newLine();
        $io->success('OK');
        $this->logOut(\sprintf('%1$d added, %2$d updated, %3$d deleted.', $adds, $updates, \count($toDelete)));

        return $this;
    }

    /**
     * Method to get comments ids of the issue
     *
     * @param   integer  $issueNumber  The issue number to get comments for
     *
     * @return  array|null  An array of comments ids or null if no data found
     *
     * @since   1.0
     */
    private function getIssueCommentsIds($issueNumber)
    {
        /** @var \Joomla\Database\DatabaseDriver $db */
        $db = $this->getContainer()->get('db');

        $query = $db->getQuery(true);

        $this->logOut('Getting issue comments.');

        return $db->setQuery(
            $query
                ->select($db->quoteName('gh_comment_id'))
                ->from($db->quoteName('#__activities'))
                ->where($db->quoteName('issue_number') . ' = ' . (int) $issueNumber)
                ->where($db->quoteName('project_id') . ' = ' . (int) $this->project->project_id)
                ->where(($db->quoteName('event')) . ' = ' . $db->quote('comment'))
        )
            ->loadColumn();
    }

    /**
     * Method to delete comments
     *
     * @param   array  $ids  An array of comments ids to delete
     *
     * @return  void
     *
     * @since   1.0
     */
    private function deleteIssuesComments(array $ids)
    {
        /** @var \Joomla\Database\DatabaseDriver $db */
        $db = $this->getContainer()->get('db');

        $query = $db->getQuery(true);

        $this->logOut('Deleting issues comments.');

        $db->setQuery(
            $query
                ->delete($db->quoteName('#__activities'))
                ->where($db->quoteName('gh_comment_id') . ' IN (' . implode(',', $ids) . ')')
        )
            ->execute();
    }
}
