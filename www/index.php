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

// Instantiate the application.
$application = new Joomla\Tracker\Application\SiteApplication;

// Execute the application.
$application->execute();
