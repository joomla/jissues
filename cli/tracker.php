#!/usr/bin/env php
<?php
/**
 * @package     JTracker
 * @subpackage  CLI
 *
 * @copyright   Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

'cli' == PHP_SAPI
	|| die("\nThis script must be run from the command line interface.\n\n");

version_compare(PHP_VERSION, '5.3.10') >= 0
	|| die("\nThis script requires PHP version >= 5.3.10 (" . PHP_VERSION . ")\n\n");

// Configure error reporting to maximum for CLI output.
error_reporting(-1);
ini_set('display_errors', 1);

$loader = require __DIR__ . '/../vendor/autoload.php';

// Add the namespace for our application to the autoloader.
$loader->add('CliApp', __DIR__);

use CliApp\Application\TrackerApplication;

// @todo remove
use Joomla\Factory;

try
{
	$application = new TrackerApplication;

	// @todo remove
	Factory::$application = $application;

	$application->execute();
}
catch (\Exception $e)
{
	echo "\nERROR: " . $e->getMessage() . "\n\n";

	echo $e->getTraceAsString();

	exit($e->getCode() ? : 1);
}
