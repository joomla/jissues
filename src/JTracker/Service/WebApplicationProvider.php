<?php
/**
 * Part of the Joomla Tracker Service Package
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Service;

use Joomla\Application\AbstractWebApplication;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\Dispatcher;
use Joomla\Input\Input;
use Joomla\Router\Router;
use JTracker\Application;
use JTracker\Router\TrackerRouter;
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
					$application->setDispatcher($container->get(Dispatcher::class));
					$application->setRouter($container->get(TrackerRouter::class));

					return $application;
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

		$container->alias('router', TrackerRouter::class)
			->alias(Router::class, TrackerRouter::class)
			->share(
				TrackerRouter::class,
				function (Container $container)
				{
					return (new TrackerRouter($container, $container->get(Input::class)))
						->setControllerPrefix('\\App')
						->setDefaultController('\\Tracker\\Controller\\DefaultController');
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
