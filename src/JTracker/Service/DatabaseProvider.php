<?php
/**
 * Part of the Joomla Tracker Service Package
 *
 * @copyright  Copyright (C) 2012-2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Service;

use Joomla\Database\DatabaseDriver;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

use JTracker\Database\Migrations;

use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;

/**
 * Database service provider
 *
 * @since  1.0
 */
class DatabaseProvider implements ServiceProviderInterface
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
		$container->set('Joomla\\Database\\DatabaseDriver',
			function (Container $container)
			{
				$app = $container->get('app');

				/*
				 * The `mysql` driver corresponds to the Framework's PDO MySQL driver and requires 'charset' => 'utf8mb4'
				 * The `mysqli` driver corresponds to the Framework's MySQLi driver and requires 'utf8mb4' => true
				 *
				 * The options are unique to each driver and do not cause misconfigurations across drivers
				 */
				$options = [
					'driver'   => $app->get('database.driver'),
					'host'     => $app->get('database.host'),
					'user'     => $app->get('database.user'),
					'password' => $app->get('database.password'),
					'database' => $app->get('database.name'),
					'prefix'   => $app->get('database.prefix'),
					'utf8mb4'  => true,
					'charset'  => 'utf8mb4',
				];

				$db = DatabaseDriver::getInstance($options);
				$db->setDebug($app->get('debug.database', false));
				$db->setLogger($container->get('monolog.logger.database'));

				return $db;
			}, true, true
		);

		// Alias the database
		$container->alias('db', 'Joomla\\Database\\DatabaseDriver');

		$container->set('JTracker\\Database\\Migrations',
			function (Container $container)
			{
				return new Migrations(
					$container->get('db'),
					new Filesystem(new Local(JPATH_CONFIGURATION))
				);
			}, true, true
		);

		// Alias the migrator
		$container->alias('db.migrations', 'JTracker\\Database\\Migrations');
	}
}
