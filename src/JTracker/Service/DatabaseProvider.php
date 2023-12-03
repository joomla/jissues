<?php
/**
 * Part of the Joomla Tracker Service Package
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Service;

use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseEvents;
use Joomla\Database\DatabaseFactory;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\Event\ConnectionEvent;
use Joomla\Database\Monitor\DebugMonitor;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\Dispatcher;

use JTracker\Database\Migrations;

use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use Monolog\Logger;
use Psr\Log\LogLevel;

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
					/** @var \Joomla\Registry\Registry $config */
					$config  = $container->get('config');

					$options = [
						'driver'   => $config->get('database.driver'),
						'host'     => $config->get('database.host'),
						'user'     => $config->get('database.user'),
						'password' => $config->get('database.password'),
						'database' => $config->get('database.name'),
						'prefix'   => $config->get('database.prefix'),
					];

					// Apply extra options based on the active driver
					switch ($options['driver'])
					{
						case 'mysql':
							$options['charset'] = 'utf8mb4';

							break;

						case 'mysqli':
							$options['utf8mb4'] = true;

							break;
					}

					if ($config->get('debug.database', false))
					{
						$options['monitor'] = new DebugMonitor;

						/** @var Dispatcher $dispatcher */
						$dispatcher = $container->get(Dispatcher::class);

						$dispatcher->addListener(
							DatabaseEvents::POST_DISCONNECT,
							function (ConnectionEvent $event) use ($container) {
								$driver = $event->getDriver();

								if ($driver instanceof DatabaseDriver)
								{
									/** @var DebugMonitor $monitor */
									$monitor = $driver->getMonitor();

									/** @var Logger $logger */
									$logger = $container->get('monolog.logger.database');

									foreach ($monitor->getLogs() as $log)
									{
										$logger->log(LogLevel::DEBUG, $log, ['category' => 'databasequery']);
									}
								}
							}
						);
					}

					/** @var DatabaseDriver $driver */
					$driver = $container->get(DatabaseFactory::class)->getDriver($options['driver'], $options);

					return $driver;
				}
			);

		$container->share(
			DatabaseFactory::class,
			function ()
			{
				return new DatabaseFactory;
			}
		);

		$container->alias('db.migrations', Migrations::class)
			->share(
				Migrations::class,
				function (Container $container)
				{
					return new Migrations(
						$container->get(DatabaseDriver::class),
						new Filesystem(new Local(JPATH_CONFIGURATION))
					);
				},
				true
			);
	}
}
