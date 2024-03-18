<?php

/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Command\Make;

use JTracker\Command\TrackerCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class for generating class doc blocks in JTracker\Database\AbstractDatabaseTable classes
 *
 * @since  1.0
 */
class Dbcomments extends TrackerCommand
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
        $this->setName('make:dbcomments');
        $this->setDescription('Generate class doc blocks for Table classes.');
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
        $ioStyle->title('Make Table Comments');

        /** @var \Joomla\Database\DatabaseDriver $db */
        $db = $this->getContainer()->get('db');

        $tables = $db->getTableList();

        $comms = [];

        foreach ($tables as $table) {
            $fields = $db->getTableColumns($table, false);

            $lines = [];

            foreach ($fields as $field) {
                $com = new \stdClass();

                $com->type    = $this->getType($field->Type);
                $com->name    = '$' . $field->Field;
                $com->comment = $field->Comment ? $field->Comment : $field->Field;

                $lines[] = $com;
            }

            $comms[$table] = $lines;
        }

        foreach ($comms as $table => $com) {
            $this->out(' * ' . $table);

            $maxVals = $this->getMaxVals($com);

            foreach ($com as $line) {
                $l = '';
                $l .= ' * @property';
                $l .= '   ' . $line->type;
                $l .= str_repeat(' ', $maxVals->maxType - \strlen($line->type));
                $l .= '  ' . $line->name;
                $l .= str_repeat(' ', $maxVals->maxName - \strlen($line->name));
                $l .= '  ' . $line->comment;

                $ioStyle->text($l);
            }

            $ioStyle->newLine();
        }

        $ioStyle->newLine();
        $ioStyle->success('Finished.');

        return Command::SUCCESS;
    }

    /**
     * Get the maximum values to align doc comments.
     *
     * @param   array  $lines  The doc comment.
     *
     * @return  \stdClass
     *
     * @since   1.0
     */
    private function getMaxVals(array $lines)
    {
        $mType = 0;
        $mName = 0;

        foreach ($lines as $line) {
            $len   = \strlen($line->type);
            $mType = $len > $mType ? $len : $mType;

            $len   = \strlen($line->name);
            $mName = $len > $mName ? $len : $mName;
        }

        $v = new \stdClass();

        $v->maxType = $mType;
        $v->maxName = $mName;

        return $v;
    }

    /**
     * Get a PHP data type from a SQL data type.
     *
     * @param   string  $type  The SQL data type.
     *
     * @return  string
     *
     * @since   1.0
     */
    private function getType($type)
    {
        if (
            strpos($type, 'int') === 0
            || strpos($type, 'tinyint') === 0
        ) {
            return 'integer';
        }

        if (
            strpos($type, 'varchar') === 0
            || strpos($type, 'text') === 0
            || strpos($type, 'mediumtext') === 0
            || strpos($type, 'datetime') === 0
        ) {
            return 'string';
        }

        return $type;
    }
}
