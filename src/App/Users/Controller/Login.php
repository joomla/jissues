<?php
/**
 * Part of the Joomla Tracker's Users Application
 *
 * @copyright  Copyright (C) 2012-2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Users\Controller;

use Joomla\OAuth2\Client as OAuthClient;
use Joomla\Registry\Registry;
use Joomla\Github\Github;
use Joomla\Github\Http;
use Joomla\Http\HttpFactory;

use JTracker\Authentication\Exception\AuthenticationException;
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
	 * @throws  AuthenticationException
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
			throw new AuthenticationException($error, 'login');
		}

		$code = $app->input->get('code');

		if (!$code)
		{
			// No auth code supplied.
			throw new AuthenticationException($user, 'login');
		}

		// Do login
		$app->getSession()->migrate();

		$options = new Registry(
			[
				'tokenurl'     => 'https://github.com/login/oauth/access_token',
				'redirect_uri' => $app->get('uri.request'),
				'clientid'     => $app->get('github.client_id'),
				'clientsecret' => $app->get('github.client_secret'),
			]
		);

		$token = (new OAuthClient($options, HttpFactory::getHttp([], ['curl']), $app->input, $app))->authenticate();

		if (isset($token['error']))
		{
			switch ($token['error'])
			{
				case 'bad_verification_code' :
					throw new \DomainException('bad verification code');
					break;

				default :
					throw new \DomainException('Unknown (2) ' . $token['error']);
					break;
			}
		}

		if (!isset($token['access_token']))
		{
			throw new \DomainException('Can not retrieve the access token');
		}

		$accessToken = $token['access_token'];

		$loginHelper = new GitHubLoginHelper($this->getContainer());

		// Store the token into the session
		$app->getSession()->set('gh_oauth_access_token', $accessToken);

		// Get the current logged in GitHub user
		$options = new Registry;
		$options->set('gh.token', $accessToken);

		// GitHub API works best with cURL
		$transport = HttpFactory::getAvailableDriver($options, ['curl']);

		if (false === $transport)
		{
			throw new \DomainException('No transports available (please install php-curl)');
		}

		$gitHubUser = (new Github($options, new Http($options, $transport)))->users->getAuthenticatedUser();

		$user = new GitHubUser($app->getProject(), $this->getContainer()->get('db'));
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
