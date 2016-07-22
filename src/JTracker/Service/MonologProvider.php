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
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;
use Monolog\Processor\WebProcessor;

/**
 * Monolog service provider
 *
 * @since  1.0
 */
class MonologProvider implements ServiceProviderInterface
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
		// Register the PSR-3 processor
		$container->share(
			'monolog.processor.psr3',
			function ()
			{
				return new PsrLogMessageProcessor;
			}
		);

		// Register the web processor
		$container->share('monolog.processor.web',
			function ()
			{
				return new WebProcessor;
			}
		);

		// Register the main application handler
		$container->share('monolog.handler.application',
			function (Container $container)
			{
				/** @var \Joomla\Registry\Registry $config */
				$config = $container->get('config');

				$level = strtoupper($config->get('log.levels.application', $config->get('log.level', 'error')));

				return new StreamHandler(
					$config->get('debug.log-path', JPATH_ROOT) . '/app.log',
					constant('\\Monolog\\Logger::' . $level)
				);
			}
		);

		// Register the database handler
		$container->share('monolog.handler.database',
			function (Container $container)
			{
				/** @var \Joomla\Registry\Registry $config */
				$config = $container->get('config');

				// If database debugging is enabled then force the logger's error level to DEBUG, otherwise use the level defined in the app config
				$level = strtoupper($config->get('debug.database', false) ? 'debug' : $config->get('log.levels.database', $config->get('log.level', 'error')));

				return new StreamHandler(
					$config->get('debug.log-path', JPATH_ROOT) . '/database.log',
					constant('\\Monolog\\Logger::' . $level)
				);
			}
		);

		// Register the application Logger
		$container->share('monolog.logger.application',
			function (Container $container)
			{
				return new Logger(
					'JTracker',
					[
						$container->get('monolog.handler.application')
					],
					[
						$container->get('monolog.processor.web')
					]
				);
			}
		);

		// Register the database Logger
		$container->share('monolog.logger.database',
			function (Container $container)
			{
				return new Logger(
					'JTracker',
					[
						$container->get('monolog.handler.database')
					],
					[
						$container->get('monolog.processor.psr3'),
						$container->get('monolog.processor.web')
					]
				);
			}
		);
	}
}
