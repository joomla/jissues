<?php
/**
 * Part of the Joomla Tracker Service Package
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Service;

use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Input\Input;
use JTracker\Application;
use JTracker\Router\TrackerRouter;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Application service provider
 *
 * @since  1.0
 */
class ApplicationProvider implements ServiceProviderInterface
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
		$container->alias('Joomla\\Application\\AbstractWebApplication', 'JTracker\\Application')
			->share(
				'JTracker\\Application',
				function (Container $container)
				{
					$application = new Application(
						$container->get('Symfony\\Component\\HttpFoundation\\Session\\SessionInterface'),
						$container->get('Joomla\\Input\\Input'),
						$container->get('config')
					);

					// Inject extra services
					$application->setContainer($container);
					$application->setDispatcher($container->get('dispatcher'));
					$application->setLogger($container->get('monolog'));
					$application->setRouter($container->get('router'));

					return $application;
				}
			);

		$container->share(
			'Joomla\\Input\\Input',
			function ()
			{
				return new Input($_REQUEST);
			}
		);

		$container->alias('router', 'JTracker\\Router\\TrackerRouter')
			->alias('Joomla\\Router\\Router', 'JTracker\\Router\\TrackerRouter')
			->share(
				'JTracker\\Router\\TrackerRouter',
				function (Container $container)
				{
					return (new TrackerRouter($container, $container->get('Joomla\\Input\\Input')))
						->setControllerPrefix('\\App')
						->setDefaultController('\\Tracker\\Controller\\DefaultController');
				}
			);

		$container->alias('Symfony\\Component\\HttpFoundation\\Session\\Session', 'Symfony\\Component\\HttpFoundation\\Session\\SessionInterface')
			->share(
				'Symfony\\Component\\HttpFoundation\\Session\\SessionInterface',
				function (Container $container)
				{
					return new Session;
				}
			);
	}
}
