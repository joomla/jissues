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
	 * Registers the service provider with a DI container.
	 *
	 * @param   \Joomla\DI\Container  $container  The DI container.
	 *
	 * @return  Container  Returns itself to support chaining.
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	public function register(JoomlaContainer $container)
	{
		$container->set('App\\Debug\\TrackerDebugger',
			function () use ($container)
			{
				return new TrackerDebugger($container->get('app'));
			}, true, true
		);

		// Alias the object
		$container->alias('debugger', 'App\\Debug\\TrackerDebugger');
	}
}
