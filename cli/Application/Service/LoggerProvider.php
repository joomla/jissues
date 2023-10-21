<?php
/**
 * Part of the Joomla Tracker Service Package
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Application\Service;

use Application\Application;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;

use Psr\Log\NullLogger;

/**
 * Class LoggerProvider
 *
 * @since  1.0
 */
class LoggerProvider implements ServiceProviderInterface
{
	/**
	 * Registers the service provider with a DI container.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	public function register(Container $container)
	{
		$container->share(
			'monolog.logger.cli',
			function (Container $container)
			{
				/** @var Application $app */
				$app = $container->get(Application::class);

				// Instantiate the object
				$logger = new Logger('JTracker');

				if ($file = $app->getConsoleInput()->getOption('log'))
				{
					// Log to a file
					$logger->pushHandler(
						new StreamHandler(
							$container->get('debugger')->getLogPath('root') . '/' . $file,
							Logger::INFO
						)
					);
				}
				elseif ($app->getConsoleInput()->getOption('quiet'))
				{
					// Log to screen
					$logger->pushHandler(
						new StreamHandler('php://stdout')
					);
				}
				else
				{
					$logger = new NullLogger;
				}

				return $logger;
			},
			true
		);
	}
}
