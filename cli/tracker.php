#!/usr/bin/env php
<?php
/**
 * @package     JTracker
 * @subpackage  CLI
 *
 * @copyright   Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

use CliApp\Application\TrackerApplication;

'cli' == PHP_SAPI
	|| die("\nThis script must be run from the command line interface.\n\n");

version_compare(PHP_VERSION, '5.3.10') >= 0
	|| die("\nThis script requires PHP version >= 5.3.10 (Your version: " . PHP_VERSION . ")\n\n");

// Configure error reporting to maximum for CLI output.
error_reporting(-1);
ini_set('display_errors', 1);

// Load the autoloader
$loader = require __DIR__ . '/../vendor/autoload.php';

// Add the namespace for our application to the autoloader.
$loader->add('CliApp', __DIR__);

try
{
	$application = new TrackerApplication;

	// @todo remove
	Joomla\Factory::$application = $application;

	$application->execute();
}
catch (\Exception $e)
{
	echo "\n\nERROR: " . $e->getMessage() . "\n\n";

	echo $e->getTraceAsString();

	exit($e->getCode() ? : 255);
}
