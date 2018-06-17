<?php
/**
 * Part of the Joomla Tracker Service Package
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Service;

use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

use Joomla\Registry\Registry;
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
		$container->alias('db', DatabaseDriver::class)
			->alias(DatabaseInterface::class, DatabaseDriver::class)
			->share(
				DatabaseDriver::class,
				function (Container $container)
				{
					/** @var Registry $config */
					$config = $container->get('config');

					/*
					 * The `mysql` driver corresponds to the Framework's PDO MySQL driver and requires 'charset' => 'utf8mb4'
					 * The `mysqli` driver corresponds to the Framework's MySQLi driver and requires 'utf8mb4' => true
					 *
					 * The options are unique to each driver and do not cause misconfigurations across drivers
					 */
					$options = [
						'driver'   => $config->get('database.driver'),
						'host'     => $config->get('database.host'),
						'user'     => $config->get('database.user'),
						'password' => $config->get('database.password'),
						'database' => $config->get('database.name'),
						'prefix'   => $config->get('database.prefix'),
						'utf8mb4'  => true,
						'charset'  => 'utf8mb4',
					];

					$db = DatabaseDriver::getInstance($options);
					$db->setDebug($config->get('debug.database', false));
					$db->setLogger($container->get('monolog.logger.database'));

					return $db;
				},
				true
		);

		$container->alias('db.migrations', Migrations::class)
			->share(
				Migrations::class,
				function (Container $container)
				{
					return new Migrations(
						$container->get('db'),
						new Filesystem(new Local(JPATH_CONFIGURATION))
					);
				},
				true
		);
	}
}
