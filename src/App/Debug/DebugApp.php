<?php
/**
 * Part of the Joomla Tracker's Debug Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Debug;

use Joomla\DI\Container;
use JTracker\AppInterface;
use JTracker\Router\TrackerRouter;

/**
 * Debug app
 *
 * @since  1.0
 */
class DebugApp implements AppInterface
{
	/**
	 * Loads services for the component into the application's DI Container
	 *
	 * @param   Container  $container  DI Container to load services into
	 *
	 * @return  void
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	public function loadServices(Container $container)
	{
		$this->registerRouteMap($container->get('router'));
		$this->registerServices($container);
	}

	/**
	 * Registers the route mapping for the app
	 *
	 * @param   TrackerRouter  $router  The application router
	 *
	 * @return  void
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	private function registerRouteMap(TrackerRouter $router)
	{
		// Register the component routes
		$maps = json_decode(file_get_contents(__DIR__ . '/routes.json'), true);

		if (!$maps)
		{
			throw new \RuntimeException('Invalid router file for the Debug app: ' . __DIR__ . '/routes.json', 500);
		}

		$router->addMaps($maps);
	}

	/**
	 * Registers the services for the app
	 *
	 * @param   Container  $container  DI Container to load services into
	 *
	 * @return  void
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	private function registerServices(Container $container)
	{
		$container->alias('debugger', TrackerDebugger::class)
			->share(
				TrackerDebugger::class,
				function (Container $container)
				{
					return new TrackerDebugger($container);
				},
				true
			);
	}
}
