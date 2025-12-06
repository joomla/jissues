<?php

/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Command\Get;

use JTracker\Authentication\GitHub\GitHubLoginHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class for retrieving avatars from GitHub for selected projects
 *
 * @since  1.0
 */
class Avatars extends Get
{
    /**
     * The command name - available to be used as a reference for searching.
     *
     * @var    string
     * @since  2.0.0
     */
    public const COMMAND_NAME = 'get:avatars';

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
        $this->setDescription('Retrieve avatar images from GitHub.');
        $this->addProjectOption();
        $this->addProgressBarOption();
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
        $this->usePBar = $this->getApplication()->get('cli-application.progress-bar');

        if ($input->getOption('noprogress')) {
            $this->usePBar = false;
        }

        \defined('JPATH_THEMES') || \define('JPATH_THEMES', JPATH_ROOT . '/www');

        $ioStyle = new SymfonyStyle($input, $output);
        $ioStyle->title('Retrieve Avatars');

        $this->logOut('Start retrieving Avatars.')
            ->setupGitHub()
            ->fetchAvatars($ioStyle)
            ->logOut('Finished.');

        return Command::SUCCESS;
    }

    /**
     * Fetch avatars.
     *
     * @param   SymfonyStyle  $io  The output to inject into the command.
     *
     * @return  $this
     *
     * @throws  \UnexpectedValueException
     * @since   1.0
     */
    private function fetchAvatars(SymfonyStyle $io)
    {
        /** @var \Joomla\Database\DatabaseDriver $db */
        $db = $this->getContainer()->get('db');

        $usernames = $db->setQuery(
            $db->getQuery(true)
                ->from($db->quoteName('#__activities'))
                ->select('DISTINCT ' . $db->quoteName('user'))
                ->order($db->quoteName('user'))
        )->loadColumn();

        if (!\count($usernames)) {
            throw new \UnexpectedValueException('No users found in database.');
        }

        $this->logOut(
            \sprintf(
                'Processing avatars for %d users.',
                \count($usernames)
            )
        );

        $this->usePBar ? $io->progressStart(\count($usernames)) : null;

        $base = JPATH_THEMES . '/images/avatars/';
        $adds = 0;

        /** @var GitHubLoginHelper $loginHelper */
        $loginHelper = $this->getContainer()->get(GitHubLoginHelper::class);

        foreach ($usernames as $i => $username) {
            if (!$username) {
                continue;
            }

            if (file_exists($base . '/' . $username . '.png')) {
                if ($io->isVeryVerbose()) {
                    $io->text(\sprintf('User avatar already fetched for user %s', $username));
                }

                $this->usePBar
                    ? $io->progressAdvance()
                    : $io->text('-');

                continue;
            }

            if ($io->isVeryVerbose()) {
                $io->text(\sprintf('Fetching avatar for user: %s', $username));
            }

            try {
                $loginHelper->saveAvatar($username);

                $adds++;
            } catch (\DomainException $e) {
                if ($io->isVerbose()) {
                    $io->text($e->getMessage());
                }

                if ($io->isVeryVerbose()) {
                    $io->text(\sprintf('Copy default image for user: %s', $username));
                }

                copy(
                    JPATH_THEMES . '/images/avatars/user-default.png',
                    JPATH_THEMES . '/images/avatars/' . $username . '.png'
                );
            }

            $this->usePBar
                ? $io->progressAdvance()
                : $io->text('+');
        }

        $io->progressFinish();

        return $this->logOut(
            \sprintf(
                'Added %d new user avatars',
                $adds
            )
        );
    }
}
