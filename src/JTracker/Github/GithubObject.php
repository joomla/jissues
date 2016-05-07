<?php
/**
 * Part of the Joomla Framework Github Package
 *
 * @copyright  Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace JTracker\GitHub;

use Joomla\Http\Response;
use Joomla\Github\AbstractGithubObject as JGithubObject;
use JTracker\Github\Exception\GithubException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * GitHub API object class for the Joomla Framework.
 *
 * @since  1.0
 */
abstract class GithubObject extends JGithubObject implements LoggerAwareInterface
{
	use LoggerAwareTrait;

	/**
	 * @var    integer
	 * @since  1.0
	 */
	protected $rateLimitRemaining = 0;

	/**
	 * Process the response and decode it.
	 *
	 * @param   Response  $response      The response.
	 * @param   integer   $expectedCode  The expected "good" code.
	 * @param   boolean   $jsonDecode    Should the response be JSON decoded ?
	 *
	 * @return  mixed
	 *
	 * @since   1.0
	 * @throws  GithubException
	 */
	protected function processResponse(Response $response, $expectedCode = 200, $jsonDecode = true)
	{
		// Validate the response code.
		if ($response->code != $expectedCode)
		{
			throw new GithubException($response);
		}

		$this->rateLimitRemaining = (isset($response->headers['X-RateLimit-Remaining']))
			? $response->headers['X-RateLimit-Remaining']
			: 0;

		return $jsonDecode ? json_decode($response->body) : $response->body;
	}

	/**
	 * Get the number of remaining requests.
	 *
	 * @return integer
	 *
	 * @since   1.0
	 */
	public function getRateLimitRemaining()
	{
		return $this->rateLimitRemaining;
	}
}
