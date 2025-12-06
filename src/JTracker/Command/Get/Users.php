<?php

/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Command\Get;

use App\Projects\TrackerProject;
use JTracker\Authentication\GitHub\GitHubLoginHelper;
use JTracker\Authentication\GitHub\GitHubUser;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class for updating user information from GitHub.
 *
 * @since  1.0
 */
class Users extends Get
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
        $this->setName('get:users');
        $this->setDescription('Retrieve user info from GitHub.');
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
        $ioStyle->title('Retrieve Users');

        $this->logOut('Start retrieving Users.')
            ->setupGitHub()
            ->getUserName($ioStyle)
            ->out()
            ->logOut('Finished.');

        return Command::SUCCESS;
    }

    /**
     * Fetch Username and store into DB.
     *
     * @param   SymfonyStyle  $io  The output decorator
     *
     * @return  $this
     *
     * @since   1.0
     * @throws  \UnexpectedValueException
     */
    private function getUserName(SymfonyStyle $io)
    {
        /** @var \Joomla\Database\DatabaseDriver $db */
        $db = $this->getContainer()->get('db');

        /** @var \Joomla\Github\Github $github */
        $github = $this->getContainer()->get('gitHub');

        $userNames = $db->setQuery(
            $db->getQuery(true)
                ->from($db->quoteName('#__activities'))
                ->select('DISTINCT ' . $db->quoteName('user'))
                ->order($db->quoteName('user'))
        )->loadColumn();

        if (!\count($userNames)) {
            throw new \UnexpectedValueException('No users found in database.');
        }

        $io->text(
            \sprintf(
                'Getting user info for %d users.',
                \count($userNames)
            )
        );

        $this->usePBar ? $io->progressStart(\count($userNames)) : null;

        /** @var GitHubLoginHelper $loginHelper */
        $loginHelper = $this->getContainer()->get(GitHubLoginHelper::class);
        $user        = new GitHubUser(new TrackerProject($this->getContainer()->get('db')), $this->getContainer()->get('db'));

        foreach ($userNames as $i => $userName) {
            if (!$userName) {
                continue;
            }

            if ($io->isVeryVerbose()) {
                $io->text(\sprintf('Fetching User Info for user: %s', $userName));
            }

            try {
                $ghUser = $github->users->get($userName);

                $user->id = 0;

                // Refresh the user data
                $user->loadGitHubData($ghUser)
                    ->loadByUserName($user->username);

                $loginHelper->refreshUser($user);
            } catch (\Exception $exception) {
                $io->error(\sprintf('An error has occurred during user refresh: %s', $exception->getMessage()));
            }

            $this->usePBar
                ? $io->progressAdvance(1)
                : $io->write('.', false);
        }

        $io->progressFinish();
        $io->text('User information has been refreshed.');

        return $this;
    }
}
