<?php

/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Command\Get\Project;

use App\Projects\Table\LabelsTable;
use JTracker\Command\Get\Project;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class for retrieving labels from GitHub for selected projects.
 *
 * @since  1.0
 */
class Labels extends Project
{
    /**
     * The command name - available to be used as a reference for searching.
     *
     * @var    string
     * @since  2.0.0
     */
    public const COMMAND_NAME = 'get:project_labels';

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
        $this->setDescription('Retrieve project labels from GitHub.');
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
        $ioStyle->title('Retrieve Labels');

        $this->logOut('Start retrieve Labels')
            ->selectProject($input, $ioStyle)
            ->setupGitHub()
            ->fetchData($ioStyle)
            ->out()
            ->logOut('Finished.');

        return Command::SUCCESS;
    }

    /**
     * Get the project labels.
     *
     * @param   SymfonyStyle  $io  The output decorator
     *
     * @return  $this
     *
     * @since   1.0
     */
    protected function fetchData(SymfonyStyle $io)
    {
        $io->write('Fetching labels...');

        /** @var \Joomla\Database\DatabaseDriver $db */
        $db = $this->getContainer()->get('db');

        $table = new LabelsTable($db);

        $labels = $this->github->issues->labels->getList(
            $this->project->gh_user,
            $this->project->gh_project
        );

        $names = [];

        $cntUpdated = 0;
        $cntNew     = 0;

        foreach ($labels as $label) {
            try {
                $table->label_id = null;

                // Check if the label exists
                $table->load(
                    [
                        'project_id' => $this->project->project_id,
                        'name'       => $label->name,
                    ]
                );

                // Values that may have changed
                if ($table->color != $label->color) {
                    $table->color = $label->color;

                    $table->store();

                    $cntUpdated++;
                }
            } catch (\RuntimeException $e) {
                // New label
                $table->project_id = $this->project->project_id;
                $table->name       = $label->name;
                $table->color      = $label->color;

                $table->store();

                $cntNew++;
            }

            $names[] = $db->quote($label->name);
        }

        // Check for deleted labels
        $ids = $db->setQuery(
            $db->getQuery(true)
                ->from($db->quoteName($table->getTableName()))
                ->select('label_id')
                ->where($db->quoteName('project_id') . ' = ' . $this->project->project_id)
                ->where($db->quoteName('name') . ' NOT IN (' . implode(', ', $names) . ')')
        )->loadColumn();

        if ($ids) {
            // Kill the orphans
            $db->setQuery(
                $db->getQuery(true)
                    ->delete($db->quoteName($table->getTableName()))
                    ->where($db->quoteName('label_id') . ' IN (' . implode(', ', $ids) . ')')
            )->execute();
        }

        $cntDeleted = \count($ids);

        $io->success('ok');

        return $this->logOut(
            \sprintf(
                'Labels: %1$d new, %2$d updated, %3$d deleted.',
                $cntNew,
                $cntUpdated,
                $cntDeleted
            )
        );
    }
}
