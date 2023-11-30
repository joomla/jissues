<?php
/**
 * Part of the Joomla Tracker Service Package
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Service;

use Joomla\Application\AbstractWebApplication;
use Joomla\Application\Controller\ControllerResolverInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\Dispatcher;
use Joomla\Input\Input;
use Joomla\Router\Router;
use JTracker\Application\Application;
use JTracker\Controller\TrackerControllerResolver;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Web application service provider
 *
 * @since  1.0
 */
class WebApplicationProvider implements ServiceProviderInterface
{
	/**
	 * Registers the service provider with a DI container.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function register(Container $container)
	{
		$container->alias(AbstractWebApplication::class, Application::class)
			->share(
				Application::class,
				function (Container $container)
				{
					$application = new Application(
						$container->get(SessionInterface::class),
						$container->get(Input::class),
						$container->get('config')
					);

					// Inject extra services
					$application->setContainer($container);
					$application->setControllerResolver($container->get(ControllerResolverInterface::class));
					$application->setDispatcher($container->get(Dispatcher::class));
					$application->setRouter($container->get(Router::class));

					return $application;
				},
				true
			);

		$container->alias(TrackerControllerResolver::class, ControllerResolverInterface::class)
			->share(
				ControllerResolverInterface::class,
				function (Container $container)
				{
					return new TrackerControllerResolver($container);
				},
				true
			);

		$container->share(
			Input::class,
			function ()
			{
				return new Input($_REQUEST);
			},
			true
		);

		$container->alias('router', Router::class)
			->share(
				Router::class,
				function (Container $container)
				{
					return new Router;
				},
				true
			);

		$container->alias(Session::class, SessionInterface::class)
			->share(
				SessionInterface::class,
				function ()
				{
					return new Session;
				},
				true
			);
	}
}
