<?php
/**
 * Part of the Joomla Framework Github Package
 *
 * @copyright  Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace JTracker\Github\Exception;

use Joomla\Http\Response;

/**
 * Class GithubException
 *
 * @since  1.0
 */
class GithubException extends \Exception
{
	/**
	 * @var  Response
	 *
	 * @since  1.0
	 */
	protected $response;

	/**
	 * Constructor.
	 *
	 * @param   Response  $response  The response object.
	 *
	 * @since  1.0
	 */
	public function __construct(Response $response)
	{
		$error = isset($response->body) ? json_decode($response->body) : null;
		$code  = isset($response->code) ? $response->code : 1;

		$message = isset($error->message) ? $error->message : 'Invalid response received from GitHub.';

		$this->response = $response;

		parent::__construct($message, $code);
	}

	/**
	 * Get the response object.
	 *
	 * @return \Joomla\Http\Response
	 *
	 * @since  1.0
	 */
	public function getResponse()
	{
		return $this->response;
	}
}
