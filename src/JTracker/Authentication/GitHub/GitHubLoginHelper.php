<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JTracker\Authentication\GitHub;

use Joomla\Factory;
use Joomla\Http\Http;
use Joomla\Http\HttpFactory;
use Joomla\Registry\Registry;
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
	 * Constructor.
	 *
	 * @param   string  $clientId      The client id.
	 * @param   string  $clientSecret  The client secret.
	 *
	 * @since   1.0
	 */
	public function __construct($clientId, $clientSecret)
	{
		$this->clientId     = $clientId;
		$this->clientSecret = $clientSecret;
	}

	/**
	 * Method to retrieve the correct URI for login via GitHub
	 *
	 * @since   1.0
	 * @return  string  The login URI
	 */
	public function getLoginUri()
	{
		/* @type \JTracker\Application\TrackerApplication $application */
		$application = Factory::$application;

		$redirect = $application->get('uri.base.full') . 'login';

		$uri = new Uri($redirect);

		$usrRedirect = base64_encode((string) new Uri($application->get('uri.request')));

		$uri->setVar('usr_redirect', $usrRedirect);

		$redirect = (string) $uri;

		// Use "raw URI" here to partial encode the url.
		return 'https://github.com/login/oauth/authorize?scope=public_repo'
			. '&client_id=' . $this->clientId
			. '&redirect_uri=' . urlencode($redirect);
	}

	/**
	 * Request an oAuth token from GitHub.
	 *
	 * @param   string  $code  The code obtained form GitHub on the previous step.
	 *
	 * @throws \RuntimeException
	 * @throws \DomainException
	 *
	 * @since   1.0
	 * @return  string  The oAuth token
	 */
	public function requestToken($code)
	{
		// @todo temporary fix to avoid the "Socket" transport protocol - ADD: and the "stream"...
		$options = new Registry;
		$transport = HttpFactory::getAvailableDriver($options, array('curl'));

		if (false == is_a($transport, 'Joomla\\Http\\Transport\\Curl'))
		{
			throw new \RuntimeException('Please enable cURL.');
		}

		$http = new Http($options, $transport);

		$data = array(
			'client_id'     => $this->clientId,
			'client_secret' => $this->clientSecret,
			'code'          => $code
		);

		$response = $http->post(
			'https://github.com/login/oauth/access_token',
			$data,
			array('Accept' => 'application/json')
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
	 * @param   GithubUser  $user  The user.
	 *
	 * @throws \RuntimeException
	 *
	 * @return void
	 */
	public static function saveAvatar(GithubUser $user)
	{
		$path = JPATH_THEMES . '/images/avatars/' . $user->username . '.png';

		if (false == file_exists($path))
		{
			if (false == function_exists('curl_setopt'))
			{
				throw new \RuntimeException('cURL is not installed - no avatar support ;(');
			}

			$ch = curl_init($user->avatar_url);

			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

			$data = curl_exec($ch);

			curl_close($ch);

			if ($data)
			{
				file_put_contents($path, $data);
			}
		}
	}

	/**
	 * Get an avatar path.
	 *
	 * @param   GitHubUser  $user  The user.
	 *
	 * @return string
	 */
	public static function getAvatarPath(GitHubUser $user)
	{
		static $avatars = array();

		if (array_key_exists($user->username, $avatars))
		{
			return $avatars[$user->username];
		}

		$base = JPATH_THEMES . '/images/avatars/';

		$avatar = $base . '/' . $user->username . '.png';

		$avatars[$user->username] = file_exists($avatar) ? $avatar : $base . '/user-default.png';

		return $avatars[$user->username];
	}
}
