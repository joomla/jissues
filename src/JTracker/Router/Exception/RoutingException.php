<?php
/**
 * Part of the Joomla Tracker Router Package
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JTracker\Router\Exception;

/**
 * RoutingException
 *
 * @since  1.0
 */
class RoutingException extends \Exception
{
	/**
	 * The raw route.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $rawRoute = '';

	/**
	 * Constructor.
	 *
	 * @param   string  $rawRoute  The raw route.
	 *
	 * @since   1.0
	 */
	public function __construct($rawRoute)
	{
		$this->rawRoute = $rawRoute;

		parent::__construct('Bad Route', 404);
	}

	/**
	 * Get the raw route.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	public function getRawRoute()
	{
		return $this->rawRoute;
	}
}
