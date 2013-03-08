<?php
/**
 * @package     Joomla.Platform
 * @subpackage  GitHub
 *
 * @copyright   Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('JPATH_PLATFORM') or die;

/**
 * GitHub API Meta class.
 *
 * @package     Joomla.Platform
 * @subpackage  GitHub
 * @since       13.1
 */
class JGithubMeta extends JGithubObject
{
	/**
	 * Method to get the authorized IP addresses for services
	 *
	 * @return  array  Authorized IP addresses
	 *
	 * @since   13.1
	 * @throws  DomainException
	 */
	public function getMeta()
	{
		// Build the request path.
		$path = '/meta';

		return $this->processResponse($this->client->get($this->fetchUrl($path)), 200);
	}
}
