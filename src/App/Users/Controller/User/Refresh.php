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
		/* @type \JTracker\Application $application */
		$application = $this->getContainer()->get('app');

		$id = $application->input->getUint('id');

		if (!$id)
		{
			throw new \UnexpectedValueException('No id given', 404);
		}

		if (!$application->getUser()->check('admin'))
		{
			if ($application->getUser()->id != $id)
			{
				$application->enqueueMessage(
					g11n3t('You are not authorised to refresh this user.'), 'error'
				)
					->redirect(
					$application->get('uri.base.path') . 'user/' . $id
				);
			}
		}

		/* @type \Joomla\Github\Github $github */
		$gitHub = $this->getContainer()->get('gitHub');

		$loginHelper = new GitHubLoginHelper($this->getContainer());

		$gitHubUser = $gitHub->users->getAuthenticatedUser();

		$user = new GitHubUser($application->getProject(), $this->getContainer()->get('db'));

		$user->loadGitHubData($gitHubUser)
			->loadByUserName($user->username);

		try
		{
			// Refresh the user data
			$loginHelper->refreshUser($user);

			$application->enqueueMessage(
				g11n3t('The profile has been refreshed.'), 'success'
			);
		}
		catch (\Exception $exception)
		{
			$application->enqueueMessage(
				g11n3t(sprintf('An error has occurred during user refresh: %s', $exception->getMessage())), 'error'
			);
		}

		$application->redirect(
			$application->get('uri.base.path') . 'user/' . $id
		);

		return parent::execute();
	}
}
