<?php

/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2013 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JTracker\Command\Test;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class for running a test suite.
 *
 * @since  1.0
 */
class Run extends Test
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
        $this->setName('test:run');
        $this->setDescription('Run all tests.');
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
        $ioStyle->title('Test Suite');

        /** @var Phpunit $statusUT */
        $statusUT = $this->getApplication()->getCommand(Phpunit::COMMAND_NAME);
        $statusUT->setExit(false);
        $statusUT = $statusUT->execute($input, $output);

        $status = ($statusUT) ? Command::FAILURE : Command::SUCCESS;

        $this
            ->out()
            ->out(
                $status
                    ? '<error>Test Suite Finished with errors.</error>'
                    : '<ok>Test Suite Finished.</ok>'
            );

        return $status;
    }
}
