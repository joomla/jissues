<?php
/**
 * Part of the Joomla Tracker
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JTracker;

use Joomla\DI\Container as JoomlaContainer;

/**
 * Extended DI Container for the Joomla Tracker application
 *
 * @since  1.0
 */
class Container extends JoomlaContainer
{
	/**
	 * Container instance
	 *
	 * @var    Container
	 * @since  1.0
	 */
	private static $instance;

	/**
	 * Retrieve an instance of Container
	 *
	 * @return  Container
	 *
	 * @since   1.0
	 */
	public static function getInstance()
	{
		if (is_null(static::$instance))
		{
			static::$instance = new static;
		}

		return static::$instance;
	}

	/**
	 * Convenience method to retrieve a bound object
	 *
	 * @param   string   $key       Name of the dataStore key to get.
	 * @param   boolean  $forceNew  True to force creation and return of a new instance.
	 *
	 * @return  mixed   Results of running the $callback for the specified $key.
	 *
	 * @since   1.0
	 */
	public static function retrieve($key, $forceNew = false)
	{
		return static::getInstance()->get($key, $forceNew);
	}
}
