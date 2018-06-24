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
use JTracker\Github\Github;

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

		/** @var \JTracker\Application $application */
		$application = $this->container->get('app');

		$uri = new Uri($application->get('uri.base.full') . 'login');

		$uri->setVar('usr_redirect', base64_encode((string) new Uri($application->get('uri.request'))));

		return (new Github)->authorization->getAuthorizationLink(
			$this->clientId, (string) $uri, $application->get('github.auth_scope', 'public_repo')
		);
	}

	/**
	 * Save an avatar.
	 *
	 * NOTE: A redirect is expected while fetching the avatar.
	 *
	 * @param   string   $username      The username to retrieve the avatar for.
	 * @param   boolean  $forceRefresh  Force refreshing the avatar.
	 *
	 * @return  integer  The function returns the number of bytes that were written to the file, or false on failure.
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 * @throws  \DomainException
	 */
	public function saveAvatar($username, bool $forceRefresh = false)
	{
		$path = $this->avatarPath . '/' . $username . '.png';

		if (file_exists($path))
		{
			if (!$forceRefresh)
			{
				return 1;
			}

			if (false === unlink($path))
			{
				throw new \DomainException('Can not remove: ' . $path);
			}
		}

		if (false === function_exists('curl_setopt'))
		{
			throw new \RuntimeException('cURL is not installed - no avatar support.');
		}

		/** @var \Joomla\Github\Github $github */
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
		static $avatars = [];

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
	 * @return  $this
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

		$this->saveAvatar($user->username, true);

		// Refresh user data in database.

		/** @var \Joomla\Database\DatabaseDriver $db */
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
		/** @var \Joomla\Database\DatabaseDriver $db */
		$db = $this->container->get('db');

		$db->setQuery(
			$db->getQuery(true)
				->update($db->quoteName('#__users'))
				->set($db->quoteName('lastvisitDate') . '=' . $db->quote((new Date)->format($db->getDateFormat())))
				->where($db->quoteName('id') . '=' . (int) $id)
		)->execute();
	}
}
