<?php

/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Command\Get;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\OutputStyle;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class for retrieving repository tags from GitHub based on the composer file.
 *
 * @since  1.0
 */
class Composertags extends Get
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
        $this->setName('get:composertags');
        $this->setDescription('Retrieve a list of project tags from GitHub and show their installed versions.');
        $this->addProjectOption();
        $this->addOption('all', '', InputOption::VALUE_OPTIONAL, 'Show all tags or only the most recent.', false);
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
        $ioStyle->title('Retrieve Composer tags');

        $path = JPATH_ROOT . '/vendor/composer/installed.json';

        $packages = json_decode(file_get_contents($path));

        if (!$packages) {
            throw new \UnexpectedValueException(\sprintf('Can not read the packages file at %s', $path));
        }

        $this->logOut('Start getting Composer tags.')
            ->setupGitHub()
            ->displayGitHubRateLimit($ioStyle)
            ->fetchTags($ioStyle, $packages, $input->getOption('all'));
        $ioStyle->newLine();
        $this->logOut('Finished.');

        return Command::SUCCESS;
    }

    /**
     * Fetch Tags.
     *
     * @param   OutputStyle  $io        Fetch all tags or only the "most recent".
     * @param   array        $packages  List of installed packages
     * @param   boolean      $allTags   Fetch all tags or only the "most recent".
     *
     * @return  $this
     *
     * @since   1.0
     */
    private function fetchTags(OutputStyle $io, array $packages, $allTags = false)
    {
        foreach ($packages as $package) {
            $io->text($package->name);

            if (!preg_match('|https://github.com/([A-z0-9\-]+)/([A-z0-9\-\.]+).git|', $package->source->url, $matches)) {
                $io->text('CAN NOT PARSE: ' . $package->source->url);

                continue;
            }

            $owner = $matches[1];
            $repo  = $matches[2];

            $tags = $this->github->repositories->getListTags($owner, $repo);

            $found = false;

            foreach ($tags as $tag) {
                if ($tag->name == $package->version) {
                    $io->text($tag->name . ' <= Installed');

                    $found = true;

                    if (!$allTags) {
                        break;
                    }
                } else {
                    $io->text($tag->name);
                }
            }

            if (!$found) {
                $io->text(\sprintf('Installed: %s', $package->version));
            }

            $io->newLine();
        }

        return $this;
    }
}
