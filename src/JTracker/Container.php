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
	 * Array of aliases for service provider bindings
	 *
	 * @var    array
	 * @since  1.0
	 */
	protected $aliases = array();

	/**
	 * Method to create an alias for a service provider
	 *
	 * @param   string  $alias    The alias to create
	 * @param   string  $binding  The object to create the alias for
	 *
	 * @return  $this  Method supports chaining
	 *
	 * @since   1.0
	 */
	public function alias($alias, $binding)
	{
		$this->aliases[$alias] = $binding;

		return $this;
	}

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

	/**
	 * Method to retrieve the results of running the $callback for the specified $key;
	 *
	 * @param   string   $key       Name of the dataStore key to get.
	 * @param   boolean  $forceNew  True to force creation and return of a new instance.
	 *
	 * @return  mixed   Results of running the $callback for the specified $key.
	 *
	 * @since   1.0
	 */
	public function get($key, $forceNew = false)
	{
		if (isset($this->aliases[$key]))
		{
			$key = $this->aliases[$key];
		}

		return parent::get($key, $forceNew);
	}
}
