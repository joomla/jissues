<?php

/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Command\Database;

use JTracker\Command\TrackerCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * CLI command for checking the database migration status
 *
 * @since  1.0
 */
class Status extends TrackerCommand
{
    /**
     * Configure the command.
     *
     * @return  void
     *
     * @since   2.0.0
     */
    protected function configure(): void
    {
        $this->setName('database:status');
        $this->setDescription('Check the database migration status.');
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
        $ioStyle->title('Database Migrations: Check Status');

        /** @var \JTracker\Database\Migrations $migrations */
        $migrations = $this->getContainer()->get('db.migrations');

        $status = $migrations->checkStatus();

        if ($status['latest']) {
            $ioStyle->success('Your database is up-to-date.');
        } else {
            $ioStyle->comment(
                \sprintf(
                    'Your database is not up-to-date. You are missing %d migrations.',
                    $status['missingMigrations']
                )
            );
            $ioStyle->newLine();
            $ioStyle->comment(
                [
                    \sprintf('Current Version: %1$s', $status['currentVersion']),
                    \sprintf('Latest Version: %1$s', $status['latestVersion']),
                ]
            );
            $ioStyle->newLine(2);

            // TODO: Validate how the <question> element works with symfony output
            $ioStyle->text('To update, run the <question>database:migrate</question> command.');
        }

        return Command::SUCCESS;
    }
}
