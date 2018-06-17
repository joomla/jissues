<?php
/**
 * Joomla Tracker Web Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

// Define required paths
define('JPATH_ROOT',          dirname(__DIR__));
define('JPATH_CONFIGURATION', JPATH_ROOT . '/etc');
define('JPATH_THEMES',        JPATH_ROOT . '/www');
define('JPATH_TEMPLATES',     JPATH_ROOT . '/templates');
define('JTRACKER_START_TIME', microtime(true));
define('JTRACKER_START_MEMORY', memory_get_usage());

(function ()
{
	// Load the Composer autoloader
	$path = realpath(JPATH_ROOT . '/vendor/autoload.php');

	if (!$path)
	{
		header('HTTP/1.1 500 Internal Server Error', null, 500);
		header('Content-Type: text/html; charset=utf-8');
		echo 'ERROR: Composer not properly set up! Run "composer install" or see README.md for more details' . PHP_EOL;

		exit(1);
	}

	include $path;

	// Wrap in a try/catch so we can display an error if need be
	try
	{
		$container = (new Joomla\DI\Container)
			->registerServiceProvider(new JTracker\Service\CacheProvider)
			->registerServiceProvider(new JTracker\Service\ConfigurationProvider)
			->registerServiceProvider(new JTracker\Service\DatabaseProvider)
			->registerServiceProvider(new JTracker\Service\DebuggerProvider)
			->registerServiceProvider(new JTracker\Service\DispatcherProvider)
			->registerServiceProvider(new JTracker\Service\GitHubProvider)
			->registerServiceProvider(new JTracker\Service\MonologProvider)
			->registerServiceProvider(new JTracker\Service\RendererProvider)
			->registerServiceProvider(new JTracker\Service\WebApplicationProvider);

		// Create the application aliases for the common 'app' key and base application class
		$container->alias('Joomla\\Application\\AbstractApplication', 'JTracker\\Application')
			->alias('app', 'JTracker\\Application');

		// Create the logger aliases for the common 'monolog' key, the Monolog Logger class, and the PSR-3 interface
		$container->alias('monolog', 'monolog.logger.application')
			->alias('logger', 'monolog.logger.application')
			->alias('Monolog\\Logger', 'monolog.logger.application')
			->alias('Psr\\Log\\LoggerInterface', 'monolog.logger.application');

		// Set error reporting based on config
		$errorReporting = (int) $container->get('config')->get('system.error_reporting', 0);
		error_reporting($errorReporting);
		ini_set('display_errors', (bool) $errorReporting);
	}
	catch (\Exception $e)
	{
		if (isset($container))
		{
			// Try to write to a log
			try
			{
				$logger = $container->get('monolog.logger.application');
				$logger->critical(
					sprintf(
						'Exception of type %1$s thrown while booting the application',
						get_class($e)
					),
					['exception' => $e]
				);
			}
			catch (\Exception $nestedException)
			{
				// Do nothing, we tried our best
			}
		}
		else
		{
			// The container wasn't built yet, log to the PHP error log so we at least have something
			error_log($e);
		}

		header('HTTP/1.1 500 Internal Server Error', null, 500);
		header('Content-Type: text/html; charset=utf-8');
		echo 'An error occurred while booting the application: ' . $e->getMessage();

		exit(1);
	}

	// Execute the application.
	try
	{
		$app = $container->get('app');

		// Set the logger for the application.  We're doing it here because there is a recursion issue with correct service resolution that needs to be fixed.
		$app->setLogger($container->get('monolog'));

		$app->mark('Application started');
		$app->execute();
	}
	catch (\Exception $e)
	{
		// Try to write to a log
		try
		{
			$logger = $container->get('monolog.logger.application');
			$logger->critical(
				sprintf(
					'Exception of type %1$s thrown while executing the application',
					get_class($e)
				),
				['exception' => $e]
			);
		}
		catch (\Exception $nestedException)
		{
			// Do nothing, we tried our best
		}

		header('HTTP/1.1 500 Internal Server Error', null, 500);
		header('Content-Type: text/html; charset=utf-8');
		echo 'An error occurred while executing the application: ' . $e->getMessage();

		exit(1);
	}
})();
