<?php
/**
 * Part of the Joomla Tracker Service Package
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Application\Service;

use Joomla\DI\ServiceProviderInterface;
use Joomla\DI\Container;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

use Psr\Log\NullLogger;

/**
 * Class LoggerProvider
 *
 * @since  1.0
 */
class LoggerProvider implements ServiceProviderInterface
{
	/**
	 * The name of the log file.
	 *
	 * @var    string
	 * @since  1.0
	 */
	public static $fileName = '';

	/**
	 * No output
	 *
	 * @var    boolean
	 * @since  1.0
	 */
	public static $quiet = false;

	/**
	 * Constructor.
	 *
	 * @param   string   $fileName  The name of the log file.
	 * @param   boolean  $quiet     No output
	 *
	 * @since   1.0
	 */
	public function __construct($fileName = '', $quiet = false)
	{
		static::$fileName = $fileName;
		static::$quiet    = $quiet;
	}

	/**
	 * Registers the service provider with a DI container.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  Container  Returns itself to support chaining.
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	public function register(Container $container)
	{
		$container->share(
			'Monolog\\Logger',
			function () use ($container)
			{
				// Instantiate the object
				$logger = new Logger('JTracker');

				if (LoggerProvider::$fileName)
				{
					// Log to a file
					$logger->pushHandler(
						new StreamHandler(
							$container->get('debugger')->getLogPath('root') . '/' . LoggerProvider::$fileName,
							Logger::INFO
						)
					);
				}
				elseif ('1' != LoggerProvider::$quiet)
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

		// Alias the object
		$container->alias('logger', 'Monolog\\Logger');
	}
}
