<?php
/**
 * Part of the Joomla Tracker Router Package
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
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
	 * @param   string      $rawRoute  The raw route.
	 * @param   \Exception  $previous  The previous exception used for the exception chaining.
	 *
	 * @since   1.0
	 */
	public function __construct($rawRoute, \Exception $previous = null)
	{
		$this->rawRoute = $rawRoute;

		parent::__construct('Bad Route', 404, $previous);
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
