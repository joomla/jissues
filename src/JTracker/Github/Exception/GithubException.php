<?php
/**
 * Part of the Joomla Framework Github Package
 *
 * @copyright  Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace JTracker\Github\Exception;

use Joomla\Http\Exception\UnexpectedResponseException;
use Joomla\Http\Response;

/**
 * Class GithubException
 *
 * @since  1.0
 */
class GithubException extends UnexpectedResponseException
{
	/**
	 * Constructor.
	 *
	 * @param   Response  $response  The response object.
	 *
	 * @since  1.0
	 */
	public function __construct(Response $response)
	{
		$error = (string) $response->body;
		$code  = $response->getStatusCode();

		$message = $error->message ?? 'Invalid response received from GitHub.';

		parent::__construct($response, $message, $code);
	}
}
