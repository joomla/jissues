<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tracker\Authentication\GitHub;

use Joomla\Http\Http;

/**
 * Class GitHubLoginHelper.
 *
 * @since  1.0
 */
class GitHubLoginHelper
{
	private $clientId;

	private $clientSecret;

	/**
	 * Constructor.
	 *
	 * @param   string  $clientId      The client id.
	 * @param   string  $clientSecret  The client secret.
	 */
	public function __construct($clientId, $clientSecret)
	{
		$this->clientId     = $clientId;
		$this->clientSecret = $clientSecret;
	}

	/**
	 * Request an oAuth token from GitHub.
	 *
	 * @param   string  $code  The code obtained form GitHub on the previous step.
	 *
	 * @throws \DomainException
	 * @return mixed
	 */
	public function requestToken($code)
	{
		$http = new Http;

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
}
