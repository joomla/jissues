<?php
/**
 * Part of the Joomla Tracker Service Package
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Service;

use App\Debug\Database\SQLLogger;

use Doctrine\Common\EventManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Events;
use Doctrine\ORM\Tools\Setup;

use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

use JTracker\Service\Doctrine\TablePrefix;

/**
 * EntityManager service provider
 *
 * @since  1.0
 */
class EntityManagerProvider implements ServiceProviderInterface
{
	/**
	 * Registers the service provider with a DI container.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  Container  Returns itself to support chaining.
	 *
	 * @since   1.0
	 */
	public function register(Container $container)
	{
		$container->set('Doctrine\\ORM\\EntityManager',
			function () use ($container)
			{
				$application = $container->get('app');

				$isDevMode = $application->get('debug.database') ? true : false;

				// @todo more specific paths (e.g. '/src/App/[AppName]/Table') ?
				$paths = [JPATH_ROOT . '/src/App'];

				$config = Setup::createAnnotationMetadataConfiguration($paths, $isDevMode);

				if ($isDevMode)
				{
					$config->setSQLLogger(new SQLLogger($container->get('debugger')));
				}

				// Database configuration parameters
				$connectionParams = [
					'driver'   => $application->get('database.driver'),
					'host'     => $application->get('database.host'),
					'user'     => $application->get('database.user'),
					'password' => $application->get('database.password'),
					'dbname'   => $application->get('database.name'),
				];

				$eventManager = new EventManager;

				// Table Prefix
				$eventManager->addEventListener(Events::loadClassMetadata, new TablePrefix($application->get('database.prefix')));

				// Obtaining the entity manager
				return EntityManager::create($connectionParams, $config, $eventManager);
			}, true, true
		);

		// Alias the object
		$container->alias('EntityManager', 'Doctrine\\ORM\\EntityManager');
	}
}
