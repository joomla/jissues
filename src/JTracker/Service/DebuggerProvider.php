<?php
/**
 * Part of the Joomla Tracker Service Package
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Service;

use App\Debug\TrackerDebugger;

use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

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
	 * @param   Container  $container  The DI container.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	public function register(Container $container)
	{
		$container->set('App\\Debug\\TrackerDebugger',
			function () use ($container)
			{
				return new TrackerDebugger($container);
			}, true, true
		);

		// Alias the object
		$container->alias('debugger', 'App\\Debug\\TrackerDebugger');
	}
}
