<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Application\Kernel;

use Application\Application;
use Application\Service\LoggerProvider;
use Joomla\Application\AbstractApplication;
use Joomla\DI\Container;
use JTracker\Kernel;
use JTracker\Service\CliApplicationProvider;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

/**
 * Console application kernel
 *
 * @since  1.0
 */
class ConsoleKernel extends Kernel
{
	/**
	 * Run the kernel
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function run(): void
	{
		$this->boot();

		if (!$this->getContainer()->has(AbstractApplication::class))
		{
			throw new \RuntimeException('The application has not been registered with the container.');
		}

		$this->getContainer()->get(AbstractApplication::class)->execute();
	}

	/**
	 * Build the service container
	 *
	 * @return  Container
	 *
	 * @since   1.0
	 */
	protected function buildContainer(): Container
	{
		$container = parent::buildContainer();

		$container->registerServiceProvider(new CliApplicationProvider)
			->registerServiceProvider(new LoggerProvider);

		// Create the application aliases for the common 'app' key and base application class
		$container->alias(AbstractApplication::class, Application::class)
			->alias('app', Application::class);

		// Create the logger aliases for the common 'monolog' key, the Monolog Logger class, and the PSR-3 interface
		$container->alias('monolog', 'monolog.logger.cli')
			->alias('logger', 'monolog.logger.cli')
			->alias(Logger::class, 'monolog.logger.cli')
			->alias(LoggerInterface::class, 'monolog.logger.cli');

		// Set error reporting based on config
		$errorReporting = (int) $container->get('config')->get('system.error_reporting', 0);
		error_reporting($errorReporting);

		return $container;
	}
}
