<?php
/**
 * Part of the Joomla Tracker's System Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\System;

use App\System\Controller\WrongCmsController;
use Joomla\DI\Container;
use Joomla\Router\Route;
use Joomla\Router\Router;
use JTracker\AppInterface;

/**
 * System app
 *
 * @since  1.0
 */
class SystemApp implements AppInterface
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
		$this->registerRoutes($container->get('router'));
		$this->registerServices($container);
	}

	/**
	 * Registers the routes for the app
	 *
	 * @param   Router  $router  The application router
	 *
	 * @return  void
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	private function registerRoutes(Router $router)
	{
		// Register the component routes
		$maps = json_decode(file_get_contents(__DIR__ . '/routes.json'), true);

		if (!$maps)
		{
			throw new \RuntimeException('Invalid router file for the System app: ' . __DIR__ . '/routes.json', 500);
		}

		foreach ($maps as $pattern => $route)
		{
			$methods    = $route['methods'] ?? [];
			$controller = $route['controller'];
			$rules      = $route['rules'] ?? [];
			$defaults   = $route['defaults'] ?? [];

			$router->addRoute(new Route($methods, $pattern, $controller, $rules, $defaults));
		}
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
		$container->share(
			WrongCmsController::class,
			function (Container $container) {
				return new WrongCmsController;
			},
			true
		);
	}
}
