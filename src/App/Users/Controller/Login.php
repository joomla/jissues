<?php
/**
 * Part of the Joomla Tracker's Users Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Users\Controller;

use Joomla\Registry\Registry;
use Joomla\Github\Github;
use Joomla\Github\Http;
use Joomla\Http\HttpFactory;

use JTracker\Authentication\GitHub\GitHubLoginHelper;
use JTracker\Authentication\GitHub\GitHubUser;
use JTracker\Controller\AbstractTrackerController;

/**
 * Login controller class for the users component
 *
 * @since  1.0
 */
class Login extends AbstractTrackerController
{
	/**
	 * Execute the controller.
	 *
	 * @return  string  The rendered view.
	 *
	 * @since   1.0
	 * @throws  \Exception
	 */
	public function execute()
	{
		/* @type \JTracker\Application $app */
		$app = $this->getContainer()->get('app');

		$user = $app->getUser();

		if ($user->id)
		{
			// The user is already logged in.
			$app->redirect(' ');
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

		/*
		 * @todo J\oAuth scrambles our redirects - investigate..
		 *

		$options = new Registry(
			array(
				'tokenurl' => 'https://github.com/login/oauth/access_token',
				'redirect_uri' => $app->get('uri.request'),
				'clientid' => $app->get('github.client_id'),
				'clientsecret' => $app->get('github.client_secret')
			)
		);

		$oAuth = new oAuthClient($options);

		$token = $oAuth->authenticate();

		$accessToken = $token['access_token'];
		*/

		$loginHelper = new GitHubLoginHelper($this->getContainer());

		$accessToken = $loginHelper->requestToken($code);

		// Store the token into the session
		$app->getSession()->set('gh_oauth_access_token', $accessToken);

		// Get the current logged in GitHub user
		$options = new Registry;
		$options->set('gh.token', $accessToken);

		// GitHub API works best with cURL
		$transport = HttpFactory::getAvailableDriver($options, array('curl'));

		$http = new Http($options, $transport);

		// Instantiate Github
		$gitHub = new Github($options, $http);

		$gitHubUser = $gitHub->users->getAuthenticatedUser();

		$user = new GithubUser($app->getProject(), $this->getContainer()->get('db'));

		$user->loadGitHubData($gitHubUser)
			->loadByUserName($user->username);

		// Save the avatar
		$loginHelper->saveAvatar($user->username);

		// Set the last visit time
		$loginHelper->setLastVisitTime($user->id);

		// User login
		$app->setUser($user);

		$redirect = $app->input->getBase64('usr_redirect');

		$redirect = $redirect ? base64_decode($redirect) : '';

		// Set a "remember me" cookie.
		$app->setRememberMe(true);

		$app->redirect($redirect);
	}
}
