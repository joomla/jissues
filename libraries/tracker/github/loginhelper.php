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
abstract class JGithubLoginhelper extends JGithub
{
	public static $baseUri = 'https://api.github.com';

	public static $headers = array('Accept' => 'application/json');

	protected static $oAuthAccessToken = '';

	protected static $ghUserName;

	/**
	 * Login.
	 *
	 * This verifies the response received from GitHub and processes to obtain the token.
	 *
	 * @throws DomainException
	 *
	 * @return bool
	 */
	public static function login()
	{
		if (JFactory::getSession()->get('gh_oauth_access_token'))
		{
			// Already logged in
			return true;
		}

		$input = JFactory::getApplication()->input;

		try
		{
			/*
			 * 1)
			 * Verify the response (code)
			 */

			$error = $input->get('error');

			if ($error)
			{
				switch ($error)
				{
					case 'access_denied' :
						throw new DomainException('Authorization failed (1)');
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

			/*
			 * 2)
			 * Obtain the access token
			 */

			$config = JFactory::getConfig();

			$http = new JHttp;

			$uri = 'https://github.com/login/oauth/access_token';

			$data = array(
				'client_id'     => $config->get('github_client_id'),
				'client_secret' => $config->get('github_client_secret'),
				'code'          => $code
			);

			$response = $http->post($uri, $data, static::$headers);

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

			$access_token = $body->access_token;


			/*
			 * 3)
			 * Get the current logged in user
			 */

			$base = 'https://api.github.com';
			$response = $http->get($base . '/user?access_token=' . $access_token);

			if (200 != $response->code)
			{
				if (JDEBUG)
				{
					var_dump($response);
				}

				throw new DomainException('Invalid response from GitHub (3)');
			}

			$body = json_decode($response->body);

			// Store the token into the session
			JFactory::getSession()->set('gh_oauth_access_token', $access_token);
			JFactory::getSession()->set('gh_user_name', $body->login);

			self::$oAuthAccessToken = $access_token;
			self::$ghUserName = $body->login;
		}
		catch (DomainException $e)
		{
			JFactory::getApplication()->enqueueMessage($e->getMessage(), 'error');

			static::clearCredentials();

			return false;
		}

		return true;
	}

	/**
	 * Add a comment.
	 *
	 * @param   JTrackerProject  $project      The project.
	 * @param   integer          $issueNumber  The issue number.
	 * @param   string           $comment      The comment.
	 *
	 * @throws RuntimeException
	 *
	 * @return void
	 */
	public static function comment(JTrackerProject $project, $issueNumber, $comment)
	{
		if (!static::getToken())
		{
			throw new RuntimeException('Missing gh token');
		}

		$http = new JHttp;

		$owner = $project->gh_user;
		$repo  = $project->gh_project;

		$url = static::$baseUri . '/repos/' . $owner . '/' . $repo . '/issues/' . $issueNumber
			. '/comments?access_token=' . static::$oAuthAccessToken;

		$comment .= sprintf(
			'<hr />Please blame the <a href="%1$s">%2$s Application</a> for transmitting this comment.',
			'https://github.com/JTracker/jissues', 'JTracker'
		);

		$data = json_encode(array('body' => $comment));

		$headers = array('Accept' => 'application/json');

		$response = $http->post($url, $data, $headers);

		$body = json_decode($response->body);

		if (201 != $response->code)
		{
			JFactory::getApplication()->enqueueMessage('GitHub error: ' . $body->message, 'error');

			if (JDEBUG)
			{
				var_dump($response);
			}

			static::clearCredentials();

			return;
		}

		JFactory::getApplication()->enqueueMessage('Your comment has been added');
	}

	public static function getToken()
	{
		if('' == static::$oAuthAccessToken)
		{
			static::$oAuthAccessToken = JFactory::getSession()->get('gh_oauth_access_token');
		}

		return static::$oAuthAccessToken;
	}

	public static function getGhUsername()
	{
		return static::$ghUserName;
	}

	/**
	 * Clear the oAuth credentials.
	 *
	 * @return void
	 */
	public static function clearCredentials()
	{
		JFactory::getSession()->set('gh_oauth_access_token', null);
		JFactory::getSession()->set('gh_user_name', null);
	}

}
