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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * CLI command for migrating the database
 *
 * @since  1.0
 */
class Migrate extends TrackerCommand
{
    /**
     * The command name - available to be used as a reference for searching.
     *
     * @var    string
     * @since  2.0.0
     */
    public const COMMAND_NAME = 'database:migrate';

    /**
     * Configure the command.
     *
     * @return  void
     *
     * @since   2.0.0
     */
    protected function configure(): void
    {
        $this->setName(self::COMMAND_NAME);
        $this->setDescription('Migrate the database schema to a newer version.');
        $this->addOption('db_version', null, InputOption::VALUE_REQUIRED, 'Apply a specific database version.');
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
        $ioStyle->title('Database Migrations: Migrate');

        /** @var \JTracker\Database\Migrations $migrations */
        $migrations = $this->getContainer()->get('db.migrations');

        // If a version is given, we are only executing that migration
        $version = $input->getOption('db_version');

        try {
            $migrations->migrateDatabase($version);
        } catch (\Exception $exception) {
            $this->getLogger()->critical(
                'Error migrating database',
                ['exception' => $exception]
            );

            $message = \sprintf(
                'Error migrating database: %s',
                $exception->getMessage()
            );

            $ioStyle->error($message);

            return Command::FAILURE;
        }

        $this->getLogger()->info('Database migrated to latest version.');
        $ioStyle->success('Database migrated to latest version.');

        return Command::SUCCESS;
    }
}
