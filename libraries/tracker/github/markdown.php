<?php
/**
 * @package     JTracker
 * @subpackage  GitHub
 *
 * @copyright   Copyright (C) 2012 Open Source Matters. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

/**
 * GitHub API Markdown class.
 *
 * @package     JTracker
 * @subpackage  GitHub
 * @since       1.0
 */
class JGithubMarkdown extends JGithubObject
{
	/**
	 * Method to render a markdown document.
	 *
	 * @param   string  $text     The text object being parsed.
	 * @param   string  $mode     The parsing mode; valid options are 'markdown' or 'gfm'.
	 * @param   string  $context  An optional repository context, only used in 'gfm' mode.
	 *
	 * @return  string  Formatted HTML
	 *
	 * @since   1.0
	 * @throws  DomainException
	 * @throws  InvalidArgumentException
	 */
	public function render($text, $mode = 'gfm', $context = null)
	{
		// The valid modes
		$validModes = array('gfm', 'markdown');

		// Make sure the scope is valid
		if (!in_array($mode, $validModes))
		{
			throw new InvalidArgumentException(sprintf('The %s mode is not valid.  Valid modes are "gfm" or "markdown".', $mode));
		}

		// Build the request path.
		$path = '/markdown';

		// Build the request data.
		$data = str_replace('\\/', '/', json_encode(
			array(
				'text' => $text,
				'mode' => $mode,
				'context' => $context
			)
		));

		// Send the request.
		$response = $this->client->post($this->fetchUrl($path), $data);

		// Validate the response code.
		if ($response->code != 200)
		{
			// Decode the error response and throw an exception.
			$error = json_decode($response->body);
			throw new DomainException($error->message, $response->code);
		}

		return $response->body;
	}
}
