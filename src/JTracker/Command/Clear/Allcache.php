<?php

/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Command\Clear;

use JTracker\Command\TrackerCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class for clearing all cache stores.
 *
 * @since  1.0
 */
class Allcache extends TrackerCommand
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
        $this->setName('clear:allcache');
        $this->setDescription('Clear all cache stores.');
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
        $ioStyle->title('Clearing All Cache Stores');

        /** @var TrackerCommand[] $cacheCommands */
        $cacheCommands = $this->getApplication()->getAllCommands('cache');

        foreach ($cacheCommands as $command) {
            // Skip the allcache commands but run any other cache ones.
            if (\get_class($command) === self::class) {
                continue;
            }

            $command->execute($input, $output);
        }

        $ioStyle->newLine();
        $ioStyle->success('All cache stores have been cleared.');

        return Command::SUCCESS;
    }
}
