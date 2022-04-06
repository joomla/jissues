#!/usr/bin/env php
<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

// Configure error reporting to maximum for CLI output.
error_reporting(-1);
ini_set('display_errors', 1);

\define('JPATH_ROOT', realpath(__DIR__ . '/..'));
\define('JPATH_CONFIGURATION', JPATH_ROOT . '/etc');
\define('JPATH_THEMES', JPATH_ROOT . '/www');
\define('JPATH_TEMPLATES', JPATH_ROOT . '/templates');
\define('JTRACKER_START_TIME', microtime(true));
\define('JTRACKER_START_MEMORY', memory_get_usage());

(function ()
{
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
	/** @var Composer\Autoload\ClassLoader $loader */
	$loader->add('Application', __DIR__);

	try
	{
		(new \Application\Kernel\ConsoleKernel)->run();
	}
	catch (\Exception $e)
	{
		$trace = $e->getTraceAsString();

		echo "\n\n"
			. 'ERROR: ' . $e->getMessage()
			. "\n\n"
			. 'Call stack:' . "\n"
			. str_replace(JPATH_ROOT, 'JPATH_ROOT', $e->getTraceAsString());

		exit($e->getCode() ? : 255);
	}
})();
