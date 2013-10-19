<?php
/**
 * Part of the Joomla Tracker Service Package
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JTracker\Service;

use App\Debug\TrackerDebugger;

use Joomla\DI\Container as JoomlaContainer;
use Joomla\DI\ServiceProviderInterface;

use JTracker\Container;

/**
 * Debug service provider
 *
 * @since  1.0
 */
class DebuggerProvider implements ServiceProviderInterface
{
	/**
	 * Object instance
	 *
	 * @var    TrackerDebugger
	 * @since  1.0
	 */
	private static $object;

	/**
	 * Registers the service provider with a DI container.
	 *
	 * @param   \Joomla\DI\Container $container The DI container.
	 *
	 * @return  Container  Returns itself to support chaining.
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	public function register(JoomlaContainer $container)
	{
		if (is_null(static::$object))
		{
			$app = Container::retrieve('app');

			static::$object = new TrackerDebugger($app);
		}

		$object = static::$object;

		$container->set('App\\Debug\\TrackerDebugger', function () use ($object)
			{
				return $object;
			}, true, true
		);

		// Alias the object
		$container->alias('debugger', 'App\\Debug\\TrackerDebugger');
	}
}
