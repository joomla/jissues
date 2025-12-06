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
 * Class for generating repository information.
 *
 * @since  1.0
 */
class Repoinfo extends TrackerCommand
{
    /**
     * The command name - available to be used as a reference for searching.
     *
     * @var    string
     * @since  2.0.0
     */
    public const COMMAND_NAME = 'make:repoinfo';

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
        $this->setDescription('Generate repository information.');
    }

    /**
     * Execute the command.
     *
     * @param   InputInterface   $input   The input to inject into the command.
     * @param   OutputInterface  $output  The output to inject into the command.
     *
     * @return  integer
     *
     * @throws  \DomainException
     * @since   1.0
     */
    protected function doExecute(InputInterface $input, OutputInterface $output): int
    {
        $path    = JPATH_ROOT . '/current_SHA';
        $shaPath = JPATH_ROOT . '/sha.txt';

        $ioStyle = new SymfonyStyle($input, $output);
        $ioStyle->title('Generate repository information');

        $this->logOut('Generating Repoinfo.');

        $info   = $this->execCommand('cd ' . JPATH_ROOT . ' && git describe --long --abbrev=10 --tags 2>&1');
        $branch = $this->execCommand('cd ' . JPATH_ROOT . ' && git rev-parse --abbrev-ref HEAD 2>&1');
        $sha    = trim($this->execCommand('cd ' . JPATH_ROOT . ' && git rev-parse --short HEAD 2>&1'));

        if (file_put_contents($path, $info . ' ' . $branch) === false) {
            $this->logOut(\sprintf('Can not write to path: %s', str_replace(JPATH_ROOT, 'J_ROOT', $path)));

            throw new \DomainException('Can not write to path: ' . $path);
        }

        if (file_put_contents($shaPath, $sha) === false) {
            $this->logOut(\sprintf('Can not write to path: %s', str_replace(JPATH_ROOT, 'J_ROOT', $shaPath)));

            throw new \DomainException('Can not write to path: ' . $shaPath);
        }

        $this->logOut(\sprintf('Wrote repoinfo file to: %s', str_replace(JPATH_ROOT, 'J_ROOT', $path)));

        $ioStyle->newLine();
        $ioStyle->success('Finished.');

        return Command::SUCCESS;
    }
}
