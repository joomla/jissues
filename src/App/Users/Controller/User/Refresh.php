<?php

/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Users\Controller\User;

use JTracker\Authentication\GitHub\GitHubLoginHelper;
use JTracker\Authentication\GitHub\GitHubUser;
use JTracker\Controller\AbstractTrackerController;

/**
 * Controller class to refresh user information with data stored on GitHub.
 *
 * @since  1.0
 */
class Refresh extends AbstractTrackerController
{
    /**
     * Execute the controller.
     *
     * @return  string  The rendered view.
     *
     * @since   1.0
     * @throws  \UnexpectedValueException
     */
    public function execute()
    {
        /** @var \JTracker\Application\Application $app */
        $app = $this->getContainer()->get('app');

        $id = $app->getUser()->id;

        if (!$id) {
            throw new \UnexpectedValueException('Not authenticated.');
        }

        /** @var \Joomla\Github\Github $github */
        $gitHub = $this->getContainer()->get('gitHub');

        $gitHubUser = $gitHub->users->getAuthenticatedUser();

        $user = (new GitHubUser($app->getProject(), $this->getContainer()->get('db')))
            ->loadGitHubData($gitHubUser);

        $user->loadByUserName($user->username);

        try {
            // Refresh the user data
            /** @var GitHubLoginHelper $loginHelper */
            $loginHelper = $this->getContainer()->get(GitHubLoginHelper::class);

            $loginHelper->refreshUser($user);

            $app->enqueueMessage('The profile has been refreshed.', 'success');
        } catch (\Exception $exception) {
            $app->enqueueMessage(
                \sprintf('An error has occurred during user refresh: %s', $exception->getMessage()),
                'error'
            );
        }

        $app->redirect($app->get('uri.base.path') . 'account');

        return parent::execute();
    }
}
