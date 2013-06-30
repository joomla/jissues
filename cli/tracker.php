#!/usr/bin/env php
<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

use CliApp\Application\CliApplication;
use Joomla\Factory;

'cli' == PHP_SAPI
	|| die("\nThis script must be run from the command line interface.\n\n");

version_compare(PHP_VERSION, '5.3.10') >= 0
	|| die("\nThis application requires PHP version >= 5.3.10 (Your version: " . PHP_VERSION . ")\n\n");

// Configure error reporting to maximum for CLI output.
error_reporting(-1);
ini_set('display_errors', 1);

// Load the autoloader
$loader = require __DIR__ . '/../vendor/autoload.php';

// Add the namespace for our application to the autoloader.
$loader->add('CliApp', __DIR__);

define('JPATH_ROOT', realpath(__DIR__ . '/..'));

/**
 * Return the given object. Useful for chaining.
 *
 * This is a legacy function to ease the transition from PHP 5.3 to 5.4
 *
 * @param   mixed  $object  The object
 *
 * @return mixed
 *
 * @since  1.0
 */
function with($object)
{
	return $object;
}

try
{
	$application = new CliApplication;

	Factory::$application = $application;

	$application->execute();
}
catch (\Exception $e)
{
	echo "\n\nERROR: " . $e->getMessage() . "\n\n";

	echo $e->getTraceAsString();

	exit($e->getCode() ? : 255);
}
