<?php

/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Command\Install;

use JTracker\Command\TrackerCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\StyleInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class to install the tracker application.
 *
 * @since  1.0
 */
class Install extends TrackerCommand
{
    /**
     * Database driver object.
     *
     * @var    \Joomla\Database\DatabaseDriver
     * @since  1.0
     */
    private $db;

    /**
     * Configure the command.
     *
     * @return  void
     *
     * @since   2.0.0
     */
    protected function configure(): void
    {
        $this->setName('install');
        $this->setDescription('Install the application.');
        $this->addOption('reinstall', null, InputOption::VALUE_NONE, 'Reinstall the application (without confirmation).');
    }

    /**
     * Execute the command.
     *
     * @param   InputInterface   $input   The input to inject into the command.
     * @param   OutputInterface  $output  The output to inject into the command.
     *
     * @return  integer
     *
     * @throws  \RuntimeException
     * @throws  \UnexpectedValueException
     * @since   1.0
     */
    protected function doExecute(InputInterface $input, OutputInterface $output): int
    {
        $ioStyle = new SymfonyStyle($input, $output);
        $ioStyle->title('Installer');

        $this->db = $this->getContainer()->get('db');

        try {
            // Check if the database "exists"
            $tables = $this->db->getTableList();

            if (!$input->getOption('reinstall')) {
                $ioStyle->newLine();
                $ioStyle->text('<fg=black;bg=yellow>WARNING: A database has been found!</fg=black;bg=yellow>');
                $ioStyle->newLine(2);
                $ioStyle->text('Do you want to reinstall?');
                $ioStyle->newLine();

                $question = new ChoiceQuestion(
                    'Do you want to reinstall? (default: no)',
                    ['yes', 'no'],
                    'no'
                );
                $question->setErrorMessage('Chosen answer %s is invalid.');
                $reinstallAnswer = $ioStyle->askQuestion($question);

                if ($reinstallAnswer !== 'yes') {
                    return Command::SUCCESS;
                }
            }

            $this->cleanDatabase($tables, $ioStyle);
            $ioStyle->success('ok');
        } catch (\RuntimeException $e) {
            // Check if the message is "Could not connect to database."  Odds are, this means the DB isn't there or the server is down.
            if (strpos($e->getMessage(), 'Could not connect to database.') !== false) {
                // ? really..
                $ioStyle->text('No database found.');
                $ioStyle->text('Creating the database...');

                $this->db->setQuery('CREATE DATABASE ' . $this->db->quoteName($this->getApplication()->get('database.name')))
                    ->execute();

                $this->db->select($this->getApplication()->get('database.name'));

                $ioStyle->success('ok');
            } else {
                throw $e;
            }
        }

        // Perform the installation
        $this->processSql($ioStyle);
        $ioStyle->newLine();
        $ioStyle->success('Installation has been completed successfully.');

        return Command::SUCCESS;
    }

    /**
     * Cleanup the database.
     *
     * @param   array           $tables  Tables to remove.
     * @param   StyleInterface  $output  The output to inject into the command.
     *
     * @return  $this
     *
     * @since   1.0
     */
    private function cleanDatabase(array $tables, StyleInterface $output)
    {
        $output->text('Removing existing tables...');

        // Foreign key constraint fails fix
        $this->db->setQuery('SET FOREIGN_KEY_CHECKS=0')
            ->execute();

        foreach ($tables as $table) {
            if ($table == 'sqlite_sequence') {
                continue;
            }

            $this->db->dropTable($table, true);
            $output->writeln('.');
        }

        $this->db->setQuery('SET FOREIGN_KEY_CHECKS=1')
            ->execute();

        return $this;
    }

    /**
     * Process the main SQL file.
     *
     * @param   StyleInterface  $output  The output to inject into the command.
     *
     * @return  $this
     *
     * @throws  \RuntimeException
     * @throws  \UnexpectedValueException
     * @since   1.0
     */
    private function processSql(StyleInterface $output)
    {
        // Install.
        $dbType = $this->getApplication()->get('database.driver');

        if ($dbType == 'mysqli') {
            $dbType = 'mysql';
        }

        $fName = JPATH_ROOT . '/etc/' . $dbType . '.sql';

        if (file_exists($fName) === false) {
            throw new \UnexpectedValueException(\sprintf('Install SQL file for %s not found.', $dbType));
        }

        $sql = file_get_contents($fName);

        if ($sql === false) {
            throw new \UnexpectedValueException('SQL file corrupted.');
        }

        $output->text(\sprintf('Creating tables from file %s', realpath($fName)));

        foreach ($this->db->splitSql($sql) as $query) {
            $q = trim($this->db->replacePrefix($query));

            if (trim($q) == '') {
                continue;
            }

            $this->db->setQuery($q)
                ->execute();

            $output->writeln('.');
        }

        $output->success('ok');

        return $this;
    }
}
