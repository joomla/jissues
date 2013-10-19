<?php
/**
 * Part of the Joomla Tracker Service Package
 *
 * @copyright  Copyright (C) 2013 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CliApp\Service;

use Joomla\DI\Container as JoomlaContainer;
use Joomla\DI\ServiceProviderInterface;

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
	 * Object instance
	 *
	 * @var    Logger
	 * @since  1.0
	 */
	private static $object = null;

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
		if (is_null(static::$object))
		{
			if ($this->fileName)
			{
				// Instantiate the object
				static::$object = static::$object ? : new Logger('JTracker');

				// Log to a file
				static::$object->pushHandler(
					new StreamHandler(
						Container::retrieve('debugger')->getLogPath('root') . '/' . $this->fileName,
						Logger::INFO
					)
				);
			}

			if ('1' != $this->quiet)
			{
				// Instantiate the object
				static::$object = static::$object ? : new Logger('JTracker');

				// Log to screen
				static::$object->pushHandler(
					new StreamHandler('php://stdout')
				);
			}
		}

		$object = static::$object ? : new NullLogger;

		$container->set(
			'Monolog\\Logger', function () use ($object)
			{
				return $object;
			}, true, true
		);

		// Alias the object
		$container->alias('logger', 'Monolog\\Logger');
	}
}
