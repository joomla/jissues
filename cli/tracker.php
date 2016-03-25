#!/usr/bin/env php
<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

use Application\Application;

'cli' == PHP_SAPI
	|| die("\nThis script must be run from the command line interface.\n\n");

// Configure error reporting to maximum for CLI output.
error_reporting(-1);
ini_set('display_errors', 1);

define('JPATH_ROOT', realpath(__DIR__ . '/..'));

// Load the autoloader
$path = realpath(JPATH_ROOT . '/vendor/autoload.php');

if (!$path)
{
	// Do not translate!
	echo 'ERROR: Composer not properly set up! Run "composer install" or see README.md for more details.' . PHP_EOL;

	exit(1);
}

$loader = include $path;

// Add the namespace for our application to the autoloader.
/* @type Composer\Autoload\ClassLoader $loader */
$loader->add('Application', __DIR__);

// Wrap in a try/catch so we can display an error if need be
try
{
	$container = (new Joomla\DI\Container)
		->registerServiceProvider(new JTracker\Service\CliApplicationProvider)
		->registerServiceProvider(new JTracker\Service\ConfigurationProvider)
		->registerServiceProvider(new JTracker\Service\DatabaseProvider)
		->registerServiceProvider(new JTracker\Service\DebuggerProvider)
		->registerServiceProvider(new JTracker\Service\DispatcherProvider)
		->registerServiceProvider(new JTracker\Service\GitHubProvider)
		->registerServiceProvider(new JTracker\Service\MonologProvider)
		->registerServiceProvider(new JTracker\Service\TransifexProvider)
		->registerServiceProvider(new JTracker\Service\WebApplicationProvider)
		->registerServiceProvider(new \Application\Service\LoggerProvider);

	// Create the application aliases for the common 'app' key and base application class
	$container->alias('Joomla\\Application\\AbstractApplication', 'Application\\Application')
		->alias('app', 'Application\\Application');

	// Create the logger aliases for the common 'monolog' key, the Monolog Logger class, and the PSR-3 interface
	$container->alias('monolog', 'monolog.logger.cli')
		->alias('Monolog\\Logger', 'monolog.logger.cli')
		->alias('Psr\\Log\\LoggerInterface', 'monolog.logger.cli');
}
catch (\Exception $e)
{
	if (isset($container))
	{
		// Try to write to a log
		try
		{
			$logger = $container->get('monolog.logger.cli');
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

	$trace = $e->getTraceAsString();

	if (function_exists('g11n3t'))
	{
		echo "\n\n"
			. sprintf(g11n3t('ERROR: %s'), $e->getMessage())
			. "\n\n"
			. g11n3t('Call stack:') . "\n"
			. str_replace(JPATH_ROOT, 'JPATH_ROOT', $e->getTraceAsString());
	}
	else
	{
		// The language library has not been loaded yet :(
		echo "\n\n"
			. 'ERROR: ' . $e->getMessage()
			. "\n\n"
			. 'Call stack:' . "\n"
			. str_replace(JPATH_ROOT, 'JPATH_ROOT', $e->getTraceAsString());
	}

	exit($e->getCode() ? : 255);
}

try
{
	$container->get('app')->execute();
}
catch (\Exception $e)
{
	$trace = $e->getTraceAsString();

	if (function_exists('g11n3t'))
	{
		echo "\n\n"
			. sprintf(g11n3t('ERROR: %s'), $e->getMessage())
			. "\n\n"
			. g11n3t('Call stack:') . "\n"
			. str_replace(JPATH_ROOT, 'JPATH_ROOT', $e->getTraceAsString());
	}
	else
	{
		// The language library has not been loaded yet :(
		echo "\n\n"
			. 'ERROR: ' . $e->getMessage()
			. "\n\n"
			. 'Call stack:' . "\n"
			. str_replace(JPATH_ROOT, 'JPATH_ROOT', $e->getTraceAsString());
	}

	exit($e->getCode() ? : 255);
}
