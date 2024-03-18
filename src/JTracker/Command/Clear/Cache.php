<?php

/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Command\Clear;

use JTracker\Command\TrackerCommand;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class for clearing the application cache.
 *
 * @since  1.0
 */
class Cache extends TrackerCommand
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
        $this->setName('clear:cache');
        $this->setDescription('Clear the application cache.');
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
        $ioStyle->title('Clear Application Cache');

        /** @var CacheItemPoolInterface $cache */
        $cache = $this->getContainer()->get('cache');

        if ($cache->clear()) {
            $ioStyle->success('The application cache has been cleared.');
        } else {
            $ioStyle->error('There was an error clearing the application cache.');
        }

        return Command::SUCCESS;
    }
}
