<?php
/**
 * @copyright  Copyright (C) 2013 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JTracker\Router\Exception;

/**
 * Class RoutingException.
 *
 * @since  1.0
 */
class RoutingException extends \Exception
{
	/**
	 * The raw route.
	 *
	 * @since  1.0
	 * @var string
	 */
	protected $rawRoute = '';

	/**
	 * Constructor.
	 *
	 * @param   string  $rawRoute  The raw route.
	 */
	public function __construct($rawRoute)
	{
		$this->rawRoute = $rawRoute;

		parent::__construct('Bad Route', 404);
	}

	/**
	 * Get the raw route.
	 *
	 * @since  1.0
	 * @return string
	 */
	public function getRawRoute()
	{
		return $this->rawRoute;
	}
}
