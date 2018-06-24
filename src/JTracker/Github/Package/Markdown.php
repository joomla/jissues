<?php
/**
 * Part of the Joomla Framework Github Package
 *
 * @copyright  Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace JTracker\Github\Package;

use JTracker\Github\Package;

/**
 * GitHub API Markdown class.
 *
 * @todo the changes here should be committed to the core.
 *
 * @documentation http://developer.github.com/v3/markdown
 *
 * @since  1.0
 */
class Markdown extends Package
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
	 * @throws  \DomainException
	 * @throws  \InvalidArgumentException
	 */
	public function render($text, $mode = 'gfm', $context = null)
	{
		// The valid modes
		$validModes = ['gfm', 'markdown'];

		// Make sure the scope is valid
		if (!in_array($mode, $validModes))
		{
			throw new \InvalidArgumentException(
				sprintf(
					'The "%1$s" mode is not valid. Valid modes: "%2$s".',
					$mode, implode('", "', $validModes)
				)
			);
		}

		// Build the request path.
		$path = '/markdown';

		// Build the request data.
		$data = str_replace(
			'\\/',
			'/',
			json_encode(
				[
					'text'    => $text,
					'mode'    => $mode,
					'context' => $context,
				]
			)
		);

		return $this->processResponse($this->client->post($this->fetchUrl($path), $data), 200, false);
	}
}
