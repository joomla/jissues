<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tracker\Components\Users\Controller;

use Joomla\Date\Date;
use Joomla\Registry\Registry;
use Joomla\Github\Github;

use Joomla\Tracker\Authentication\Database\TableUsers;
use Joomla\Tracker\Authentication\GitHub\GitHubLoginHelper;
use Joomla\Tracker\Authentication\GitHub\GitHubUser;
use Joomla\Tracker\Controller\AbstractTrackerController;

/**
 * Class LoginController.
 *
 * @since  1.0
 */
class LoginController extends AbstractTrackerController
{
	/**
	 * Execute the controller.
	 *
	 * @since   1.0
	 * @throws \Exception
	 *
	 * @return  string  The rendered view.
	 */
	public function execute()
	{
		/* @var \Joomla\Tracker\Application\TrackerApplication $app */
		$app = $this->getApplication();

		$user = $app->getUser();

		if ($user->id)
		{
			// The user is already logged in.

			$app->redirect('');

			return '';
		}

		$error = $app->input->get('error');

		if ($error)
		{
			// GitHub reported an error.

			throw new \Exception($error);
		}

		$code = $app->input->get('code');

		if (!$code)
		{
			// No auth code supplied.

			throw new \Exception('Missing login code');
		}

		// Do login

		$loginHelper = new GitHubLoginHelper($app->get('github.client_id'), $app->get('github.client_secret'));

		$accessToken = $loginHelper->requestToken($code);

		// Store the token into the session
		$app->getSession()->set('gh_oauth_access_token', $accessToken);

		// Get the current logged in GitHub user

		$options = new Registry;
		$options->set('gh.token', $accessToken);

		$gitHub = new Github($options);

		$gitHubUser = $gitHub->users->getAuthenticatedUser();

		$user = new GithubUser;
		$user->loadGitHubData($gitHubUser);

		$table = new TableUsers($app->getDatabase());

		$table->loadByUserName($gitHubUser->login);

		if (!$table->id)
		{
			// Register a new user

			$date = new Date;
			$user->registerDate = $date->format('Y-m-d H:i:s');

			$table->bind($user)
				->store();
		}

		$user->id = $table->id;

		// User login
		$app->setUser($user);

		$app->redirect('');

		return '';
	}
}
