<?php
/**
 * Part of the Joomla Tracker Authentication Package
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Authentication\GitHub;

use Joomla\Date\Date;
use Joomla\DI\Container;
use Joomla\Http\HttpFactory;
use Joomla\Uri\Uri;

/**
 * Helper class for logging into the application via GitHub.
 *
 * @since  1.0
 */
class GitHubLoginHelper
{
	/**
	 * The client ID
	 *
	 * @var    string
	 * @since  1.0
	 */
	private $clientId;

	/**
	 * The client secret
	 *
	 * @var    string
	 * @since  1.0
	 */
	private $clientSecret;

	/**
	 * DI container
	 *
	 * @var    Container
	 * @since  1.0
	 */
	private $container;

	/**
	 * Path to user avatars
	 *
	 * @var    string
	 * @since  1.0
	 */
	private $avatarPath = '';

	/**
	 * Constructor.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @since   1.0
	 */
	public function __construct(Container $container)
	{
		$this->container = $container;

		// Single account
		$this->clientId     = $this->container->get('app')->get('github.client_id');
		$this->clientSecret = $this->container->get('app')->get('github.client_secret');

		// Multiple accounts
		if (!$this->clientId)
		{
			$gitHubAccounts = $this->container->get('app')->get('github.accounts');

			// Use credentials from the first account
			$this->clientId     = isset($gitHubAccounts[0]->client_id) ? $gitHubAccounts[0]->client_id : '';
			$this->clientSecret = isset($gitHubAccounts[0]->client_secret) ? $gitHubAccounts[0]->client_secret : '';
		}

		$this->avatarPath = JPATH_THEMES . '/images/avatars';
	}

	/**
	 * Method to retrieve the correct URI for login via GitHub
	 *
	 * @return  string  The login URI
	 *
	 * @since   1.0
	 */
	public function getLoginUri()
	{
		if (!$this->clientId)
		{
			// No clientId set - Throw some fatal error...

			return '';
		}

		/* @type \JTracker\Application $application */
		$application = $this->container->get('app');

		$redirect = $application->get('uri.base.full') . 'login';

		$uri = new Uri($redirect);

		$usrRedirect = base64_encode((string) new Uri($application->get('uri.request')));

		$uri->setVar('usr_redirect', $usrRedirect);

		$redirect = (string) $uri;

		// Use "raw URI" here to partial encode the url.
		return 'https://github.com/login/oauth/authorize?scope=' . $application->get('github.auth_scope', 'public_repo')
			. '&client_id=' . $this->clientId
			. '&redirect_uri=' . urlencode($redirect);
	}

	/**
	 * Request an oAuth token from GitHub.
	 *
	 * @param   string  $code  The code obtained form GitHub on the previous step.
	 *
	 * @return  string  The OAuth token
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 * @throws  \DomainException
	 */
	public function requestToken($code)
	{
		// GitHub API works best with cURL
		$http = HttpFactory::getHttp([], ['curl']);

		$data = [
			'client_id'     => $this->clientId,
			'client_secret' => $this->clientSecret,
			'code'          => $code
		];

		$response = $http->post(
			'https://github.com/login/oauth/access_token',
			$data,
			['Accept' => 'application/json']
		);

		if (200 != $response->code)
		{
			if (JDEBUG)
			{
				var_dump($response);
			}

			throw new \DomainException('Invalid response from GitHub (2) :(');
		}

		$body = json_decode($response->body);

		if (isset($body->error))
		{
			switch ($body->error)
			{
				case 'bad_verification_code' :
					throw new \DomainException('bad verification code');
					break;

				default :
					throw new \DomainException('Unknown (2) ' . $body->error);
					break;
			}
		}

		if (!isset($body->access_token))
		{
			throw new \DomainException('Can not retrieve the access token');
		}

		return $body->access_token;
	}

	/**
	 * Save an avatar.
	 *
	 * NOTE: A redirect is expected while fetching the avatar.
	 *
	 * @param   string  $username  The username to retrieve the avatar for.
	 *
	 * @return  integer  The function returns the number of bytes that were written to the file, or false on failure.
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 * @throws  \DomainException
	 */
	public function saveAvatar($username)
	{
		$path = $this->avatarPath . '/' . $username . '.png';

		if (file_exists($path))
		{
			return 1;
		}

		if (false === function_exists('curl_setopt'))
		{
			throw new \RuntimeException('cURL is not installed - no avatar support.');
		}

		/* @type \Joomla\Github\Github $github */
		$github = $this->container->get('gitHub');

		// GitHub API works best with cURL
		$response = HttpFactory::getHttp([], ['curl'])->get($github->users->get($username)->avatar_url);

		if ($response->code != 200)
		{
			throw new \DomainException(sprintf('Can not retrieve the avatar for user %s', $username));
		}

		$result = file_put_contents($path, $response->body);

		if (false === $result)
		{
			throw new \RuntimeException(sprintf('Can not write the avatar image to file %s', $path));
		}

		return $result;
	}

	/**
	 * Get an avatar path.
	 *
	 * @param   GitHubUser  $user  The user.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	public function getAvatarPath(GitHubUser $user)
	{
		static $avatars = array();

		if (array_key_exists($user->username, $avatars))
		{
			return $avatars[$user->username];
		}

		$path = $this->avatarPath . '/' . $user->username . '.png';

		$avatars[$user->username] = file_exists($path) ? $path : $this->avatarPath . '/user-default.png';

		return $avatars[$user->username];
	}

	/**
	 * Refresh local user information with data from GitHub.
	 *
	 * @param   GitHubUser  $user  The GitHub user object.
	 *
	 * @return $this
	 */
	public function refreshUser(GitHubUser $user)
	{
		// Refresh the avatar
		$path = $this->avatarPath . '/' . $user->username . '.png';

		if (file_exists($path))
		{
			if (false === unlink($path))
			{
				throw new \DomainException('Can not remove: ' . $path);
			}
		}

		$this->saveAvatar($user->username);

		// Refresh user data in database.

		/* @type \Joomla\Database\DatabaseDriver $db */
		$db = $this->container->get('db');

		$db->setQuery(
			$db->getQuery(true)
				->update($db->quoteName('#__users'))
				->set($db->quoteName('email') . '=' . $db->quote($user->email))
				->set($db->quoteName('name') . '=' . $db->quote($user->name))
				->where($db->quoteName('id') . '=' . (int) $user->id)

		)->execute();

		return $this;
	}

	/**
	 * Set the last visited time for a newly logged in user
	 *
	 * @param   integer  $id  The user ID to update
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function setLastVisitTime($id)
	{
		/* @type \Joomla\Database\DatabaseDriver $db */
		$db = $this->container->get('db');

		$date = new Date;

		$db->setQuery(
			$db->getQuery(true)
				->update($db->quoteName('#__users'))
				->set($db->quoteName('lastvisitDate') . '=' . $db->quote($date->format($db->getDateFormat())))
				->where($db->quoteName('id') . '=' . (int) $id)
		)->execute();
	}
}
