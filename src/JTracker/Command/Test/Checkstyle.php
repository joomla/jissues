<?php

/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Command\Test;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class for running checkstyle tests.
 *
 * @since  1.0
 */
class Checkstyle extends Test
{
    /**
     * Allowed codestyle failures, generally because of issues with the sniffer
     *
     * @var    integer
     * @since  1.0
     */
    public const ALLOWED_FAIL_COUNT = 0;

    /**
     * The command name - available to be used as a reference for searching.
     *
     * @var    string
     * @since  2.0.0
     */
    public const COMMAND_NAME = 'test:checkstyle';

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
        $this->setDescription('Run PHP CodeSniffer tests.');
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
        $ioStyle->title('Test Checkstyle');

        // Make sure coding standards are registered
        $this->execCommand('cd ' . JPATH_ROOT . ' && vendor/bin/phpcs --config-set installed_paths vendor/joomla/coding-standards 2>&1');

        $options = [];

        $options['files'] = [
            JPATH_ROOT . '/cli',
            JPATH_ROOT . '/src',
        ];

        $options['standard'] = [JPATH_ROOT . '/ruleset.xml'];

        $options['showProgress'] = true;

        $phpCs = new \PHP_CodeSniffer\Runner();
        $phpCs->checkRequirements();

        $numErrors = $phpCs->process($options);

        $this
            ->out()
            ->out(
                $numErrors > self::ALLOWED_FAIL_COUNT
                ? \sprintf('<error>Finished with %d errors</error>', $numErrors)
                : '<ok>Success</ok>'
            );

        if ($this->exit) {
            exit($numErrors > self::ALLOWED_FAIL_COUNT ? 1 : 0);
        }

        return $numErrors;
    }
}
