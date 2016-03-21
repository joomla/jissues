<?php
/**
 * Joomla Tracker Web Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

// Set error reporting for development
error_reporting(-1);

// Define required paths
define('JPATH_ROOT',          dirname(__DIR__));
define('JPATH_CONFIGURATION', JPATH_ROOT . '/etc');
define('JPATH_THEMES',        JPATH_ROOT . '/www');
define('JPATH_TEMPLATES',     JPATH_ROOT . '/templates');
define('JTRACKER_START_TIME', microtime(true));
define('JTRACKER_START_MEMORY', memory_get_usage());

// Load the Composer autoloader
$path = realpath(JPATH_ROOT . '/vendor/autoload.php');

if (!$path)
{
	header('HTTP/1.1 500 Internal Server Error', null, 500);
	echo 'ERROR: Composer not properly set up! Run "composer install" or see README.md for more details' . PHP_EOL;

	exit(1);
}

include $path;

// Execute the application.
try
{
	(new JTracker\Application)
		->execute();
}
catch (\Exception $e)
{
	header('HTTP/1.1 500 Internal Server Error', null, 500);
	echo 'Error instantiating the application - ' . $e->getMessage();
}
