<?php
/**
 * Part of the Joomla Tracker Service Package
 *
 * @copyright  Copyright (C) 2013 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CliApp\Service;

use Joomla\DI\ServiceProviderInterface;
use Joomla\DI\Container as JoomlaContainer;

use JTracker\Container;

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
	 * @var string
	 * @since  1.0
	 */
	private $fileName = '';

	/**
	 * @var boolean
	 * @since  1.0
	 */
	private $quiet = false;

	/**
	 * Constructor.
	 *
	 * @param   string   $fileName  The name of the log file.
	 * @param   boolean  $quiet     No output
	 *
	 * @since  1.0
	 */
	public function __construct($fileName = '', $quiet = false)
	{
		$this->fileName = $fileName;
		$this->quiet = $quiet;
	}

	/**
	 * Registers the service provider with a DI container.
	 *
	 * @param   \Joomla\DI\Container  $container  The DI container.
	 *
	 * @throws \RuntimeException
	 * @return  Container  Returns itself to support chaining.
	 *
	 * @since   1.0
	 */
	public function register(JoomlaContainer $container)
	{
		$container->share('Monolog\\Logger', function (JoomlaContainer $c) {

			// Instantiate the object
			$logger = new Logger('JTracker');

			if ($this->fileName)
			{
				// Log to a file
				$logger->pushHandler(
					new StreamHandler(
						$c->get('debugger')->getLogPath('root') . '/' . $this->fileName,
						Logger::INFO
					)
				);
			}
			elseif ('1' != $this->quiet)
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
		}, true);

		// Alias the object
		$container->alias('logger', 'Monolog\\Logger');
	}
}
