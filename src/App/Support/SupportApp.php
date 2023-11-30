<?php
/**
 * Part of the Joomla Tracker's Support Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Support;

use App\Support\Controller\Icons\ViewCssIconsController;
use App\Support\Model\IconsModel;
use Joomla\DI\Container;
use Joomla\Renderer\RendererInterface;
use Joomla\Router\Route;
use Joomla\Router\Router;
use JTracker\Application\AppInterface;
use JTracker\View\BaseHtmlView;

/**
 * Support app
 *
 * @since  1.0
 */
class SupportApp implements AppInterface
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
			throw new \RuntimeException('Invalid router file for the Support app: ' . __DIR__ . '/routes.json', 500);
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
			ViewCssIconsController::class,
			function (Container $container)
			{
				return new ViewCssIconsController(
					$container->get(IconsModel::class),
					$container->get('icons.list.view')
				);
			},
			true
		);

		$container->share(
			IconsModel::class,
			function ()
			{
				return new IconsModel;
			},
			true
		);

		$container->share(
			'icons.list.view',
			function (Container $container)
			{
				$view = new BaseHtmlView(
					$container->get(IconsModel::class),
					$container->get(RendererInterface::class)
				);

				$view->setLayout('support/icons.index.twig');

				return $view;
			},
			true
		);
	}
}
