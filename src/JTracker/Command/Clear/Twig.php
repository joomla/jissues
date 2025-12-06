<?php

/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Command\Clear;

use JTracker\Command\TrackerCommand;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class for clearing the Twig cache.
 *
 * @since  1.0
 */
class Twig extends TrackerCommand
{
    /**
     * The command name - available to be used as a reference for searching.
     *
     * @var    string
     * @since  2.0.0
     */
    public const COMMAND_NAME = 'clear:twig';

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
        $this->setDescription('Clear the Twig cache.');
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
     * @throws  FilesystemException
     */
    protected function doExecute(InputInterface $input, OutputInterface $output): int
    {
        $ioStyle = new SymfonyStyle($input, $output);
        $ioStyle->title('Clear Twig Cache Directory');

        if (!$this->getApplication()->get('renderer.cache', false)) {
            $ioStyle->info('Twig caching is not enabled.');

            return Command::SUCCESS;
        }

        $cacheDir     = JPATH_ROOT . '/cache';
        $twigCacheDir = $this->getApplication()->get('renderer.cache');

        $this->logOut(\sprintf('Cleaning the cache dir in "%s"', $cacheDir . '/' . $twigCacheDir));

        $filesystem = new Filesystem(new LocalFilesystemAdapter($cacheDir));

        if ($filesystem->has($twigCacheDir)) {
            $filesystem->deleteDirectory($twigCacheDir);
        }

        $ioStyle->newLine();
        $ioStyle->success('The Twig cache directory has been cleared.');

        return Command::SUCCESS;
    }
}
