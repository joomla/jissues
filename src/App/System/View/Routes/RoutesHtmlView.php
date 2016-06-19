<?php
/**
 * Part of the Joomla Tracker's Support Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\System\View\Routes;

use JTracker\View\AbstractTrackerHtmlView;

/**
 * System configuration view.
 *
 * @since  1.0
 */
class RoutesHtmlView extends AbstractTrackerHtmlView
{
	private $routes = [];

	/**
	 * Method to render the view.
	 *
	 * @return  string  The rendered view.
	 *
	 * @since   1.0
	 */
	public function render()
	{
		$this->addData('routes', $this->getRoutes());

		return parent::render();
	}

	/**
	 * Get the routes.
	 *
	 * @return array
	 *
	 * @since   1.0
	 */
	public function getRoutes()
	{
		return $this->routes;
	}

	/**
	 * Set the routes.
	 *
	 * @param   array  $routes  The routes.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	public function setRoutes(array $routes)
	{
		$this->routes = $routes;

		return $this;
	}
}
