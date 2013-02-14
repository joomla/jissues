<?php
/**
 * @package     JTracker
 * @subpackage  com_tracker
 *
 * @copyright   Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * GitHub login helper class.
 *
 * @package     JTracker
 * @subpackage  GitHub
 * @since       1.0
 */
final class JGithubLoginhelper
{
	/**
	 * Login.
	 *
	 * This verifies the response received from GitHub and processes to obtain the token.
	 *
	 * @throws DomainException
	 *
	 * @return JGithubUser
	 */
	public static function login()
	{
		if (JFactory::getUser()->id)
		{
			// Already logged in

			if (JFactory::getSession()->get('gh_oauth_access_token'))
			{
				// And the token exists

				return true;
			}
			else
			{
				// But somehow we've lost our token :(

				static::logout();

				// Proceed with login...
			}
		}

		$input = JFactory::getApplication()->input;

		try
		{
			// Verify the response (code)

			$error = $input->get('error');

			if ($error)
			{
				switch ($error)
				{
					case 'access_denied' :
						throw new DomainException('Authorization failed.');
						break;

					default :
						throw new DomainException('Unknown (1) ' . $error);

						break;
				}
			}

			$code = $input->get('code');

			if (!$code)
			{
				throw new DomainException('No code received from GitHub :(');
			}

			// Obtain the access token

			$access_token = static::requestToken($code);

			// Store the token into the session
			JFactory::getSession()->set('gh_oauth_access_token', $access_token);

			// Get the current logged in GitHub user

			$options = new JRegistry;
			$options->set('gh.token', $access_token);

			$gitHub = new JGithub($options);

			$gitHubUser = $gitHub->users->getAuthenticatedUser();

			// All good.

			return new JGithubUser($gitHubUser);
		}
		catch (DomainException $e)
		{
			JFactory::getApplication()->enqueueMessage($e->getMessage(), 'error');
		}

		// Something went wrong...

		static::logout();

		return false;
	}

	/**
	 * Clear the GitHub oAuth token.
	 *
	 * @return void
	 */
	public static function logout()
	{
		JFactory::getSession()->set('gh_oauth_access_token', null);

		JFactory::getApplication()->logout();
	}

	/**
	 * Get the GitHub oAuth token.
	 *
	 * @return string
	 */
	public static function getToken()
	{
		return JFactory::getSession()->get('gh_oauth_access_token');
	}

	/**
	 * Request an oAuth token from GitHub.
	 *
	 * @param   string  $code  The code obtained form GitHub on the previous step.
	 *
	 * @throws DomainException
	 * @return mixed
	 */
	private static function requestToken($code)
	{
		$config = JFactory::getConfig();

		$http = new JHttp;

		$uri = 'https://github.com/login/oauth/access_token';

		$data = array(
			'client_id'     => $config->get('github_client_id'),
			'client_secret' => $config->get('github_client_secret'),
			'code'          => $code
		);

		$response = $http->post($uri, $data, array('Accept' => 'application/json'));

		if (200 != $response->code)
		{
			if (JDEBUG)
			{
				var_dump($response);
			}

			throw new DomainException('Invalid response from GitHub (2) :(');
		}

		$body = json_decode($response->body);

		if (isset($body->error))
		{
			switch ($body->error)
			{
				case 'bad_verification_code' :
					throw new DomainException('bad verification code');
					break;

				default :
					throw new DomainException('Unknown (2) ' . $body->error);
					break;
			}
		}

		if (!isset($body->access_token))
		{
			throw new DomainException('Can not retrieve the access token');
		}

		return $body->access_token;
	}

}
