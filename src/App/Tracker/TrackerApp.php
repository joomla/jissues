<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker;

use App\Tracker\Twig\IssueExtension;
use App\Tracker\Twig\MilestoneExtension;
use App\Tracker\Twig\RelationExtension;
use App\Tracker\Twig\StatusExtension;
use Joomla\DI\Container;
use Joomla\Router\Router;
use JTracker\AppInterface;
use JTracker\Application;

/**
 * Tracker app
 *
 * @since  1.0
 */
class TrackerApp implements AppInterface
{
	/**
	 * Loads services for the component into the application's DI Container
	 *
	 * @param   Container  $container  DI Container to load services into
	 *
	 * @return  void
	 *
	 * @since   1.0
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
			throw new \RuntimeException('Invalid router file for the Tracker app: ' . __DIR__ . '/routes.json', 500);
		}

		foreach ($maps as $patttern => $controller)
		{
			// TODO - Routes should be identified for proper methods
			$router->all($patttern, $controller);
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
		$container->alias(MilestoneExtension::class, 'twig.extension.issue')
			->share(
				'twig.extension.issue',
				function (Container $container)
				{
					return new IssueExtension($container->get(Application::class));
				},
				true
			)
			->tag('twig.extension', ['twig.extension.issue']);

		$container->alias(MilestoneExtension::class, 'twig.extension.milestone')
			->share(
				'twig.extension.milestone',
				function (Container $container)
				{
					return new MilestoneExtension($container->get('db'));
				},
				true
			)
			->tag('twig.extension', ['twig.extension.milestone']);

		$container->alias(RelationExtension::class, 'twig.extension.relation')
			->share(
				'twig.extension.relation',
				function (Container $container)
				{
					return new RelationExtension($container->get('db'));
				},
				true
			)
			->tag('twig.extension', ['twig.extension.relation']);

		$container->alias(StatusExtension::class, 'twig.extension.status')
			->share(
				'twig.extension.status',
				function (Container $container)
				{
					return new StatusExtension($container->get('db'));
				},
				true
			)
			->tag('twig.extension', ['twig.extension.status']);
	}
}
