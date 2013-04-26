#!/usr/bin/env php
<?php
/**
 * @package     JTracker
 * @subpackage  CLI
 *
 * @copyright   Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

'cli' == PHP_SAPI || die("\nThis script must be run from the command line interface.\n\n");

version_compare(PHP_VERSION, '5.3.10') >= 0 || die("\nThis script requires PHP version >= 5.3.10 (" . PHP_VERSION . ")\n\n");

$loader = require __DIR__ . '/../vendor/autoload.php';
$loader->add('CliApp', __DIR__);

use CliApp\Application\TrackerApplication;
use CliApp\Exception\AbortException;

// @todo remove
use Joomla\Factory;

// @todo remove - used by JFactory::getConfig() and getDbo()
// define('JPATH_FRAMEWORK', 'dooo');

try
{
	$application = new TrackerApplication;

	// @todo remove
	Factory::$application = $application;

	$application->execute();
}
catch (AbortException $e)
{
	echo "\nProcess aborted.\n";

	exit(0);
}
catch (\Exception $e)
{
	echo "\nERROR: " . $e->getMessage() . "\n\n";

	echo $e->getTraceAsString();

	exit($e->getCode() ? : 1);
}
