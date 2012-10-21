<?php
/**
 * @package     JTracker
 * @subpackage  Router
 *
 * @copyright   Copyright (C) 2012 Open Source Matters. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

jimport('joomla.application.router');

/**
 * Class to create and parse routes
 *
 * @package     JTracker
 * @subpackage  Router
 * @since       1.0
 */
class JRouterTracker extends JRouter
{
	/**
	 * Function to convert a route to an internal URI.
	 *
	 * @param   JUri  $uri  The uri.
	 *
	 * @return  array
	 *
	 * @since   1.0
	 */
	public function parse($uri)
	{
		return array();
	}

	/**
	 * Function to convert an internal URI to a route
	 *
	 * @param   string  $url  The internal URL
	 *
	 * @return  string  The absolute search engine friendly URL
	 *
	 * @since   1.0
	 */
	public function build($url)
	{
		// Create the URI object
		$uri = parent::build($url);

		// Get the path data
		$route = $uri->getPath();

		// Add basepath to the uri
		$uri->setPath(JUri::base(true) . '/' . $route);

		return $uri;
	}
}
