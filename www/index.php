<?php
/**
 * @package    JTracker
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// Set error reporting for development
error_reporting(32767);

// Load the Composer autoloader
require dirname(__DIR__) . '/vendor/autoload.php';

// Load the Joomla Framework
require dirname(__DIR__) . '/vendor/joomla/framework/src/import.php';

// Define required paths
define('JPATH_BASE',   dirname(__DIR__));
define('JPATH_SITE',   JPATH_BASE);
define('JPATH_THEMES', JPATH_BASE . '/www');

// Instantiate the application.
$application = new Joomla\Tracker\Application\TrackerApplication;

// Execute the application.
$application->execute();
