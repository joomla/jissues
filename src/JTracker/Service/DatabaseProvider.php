<?php
/**
 * Part of the Joomla Tracker Service Package
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Service;

use Joomla\Database\DatabaseDriver;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Processor\PsrLogMessageProcessor;
use Monolog\Processor\WebProcessor;

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
			function () use ($container)
			{
				$app = $container->get('app');

				$options = array(
					'driver' => $app->get('database.driver'),
					'host' => $app->get('database.host'),
					'user' => $app->get('database.user'),
					'password' => $app->get('database.password'),
					'database' => $app->get('database.name'),
					'prefix' => $app->get('database.prefix')
				);

				$db = DatabaseDriver::getInstance($options);
				$db->setDebug($app->get('debug.database', false));
				$db->setLogger(
					new Logger(
						'JTracker-Database',
						[
							new StreamHandler(
								$app->get('debug.log-path', JPATH_ROOT) . '/database.log',
								Logger::ERROR
							)
						],
						[
							new PsrLogMessageProcessor,
							new WebProcessor
						]
					)
				);

				return $db;
			}, true, true
		);

		// Alias the database
		$container->alias('db', 'Joomla\\Database\\DatabaseDriver');
	}
}
