<?php

/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2013 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JTracker\Command\Test;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class for running PHPUnit tests.
 *
 * @since  1.0
 */
class Phpunit extends Test
{
    /**
     * The command name - available to be used as a reference for searching.
     *
     * @var    string
     * @since  2.0.0
     */
    public const COMMAND_NAME = 'test:phpunit';

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
        $this->setDescription('Run PHPUnit tests.');
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
        $ioStyle->title('Test PHPUnit');

        // PHPUnit has no public API to run tests programmatically, so run its CLI in a separate process,
        // from the project root like the CI does
        $command = implode(
            ' ',
            [
                escapeshellarg(PHP_BINARY),
                escapeshellarg('vendor/bin/phpunit'),
                '--configuration=phpunit.xml',
            ]
        );

        $cwd = getcwd();
        chdir(JPATH_ROOT);

        try {
            passthru($command, $returnVal);
        } finally {
            chdir($cwd);
        }

        $this
            ->out()
            ->out($returnVal ? '<error>Finished with errors.</error>' : '<ok>Success</ok>');

        if ($this->exit) {
            exit($returnVal ? 1 : 0);
        }

        return $returnVal;
    }
}
