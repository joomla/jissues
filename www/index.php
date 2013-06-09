<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// Set error reporting for development
error_reporting(32767);

// Define required paths
define('JPATH_BASE',          dirname(__DIR__));
define('JPATH_CONFIGURATION', JPATH_BASE . '/etc');
define('JPATH_ROOT',          JPATH_BASE);
define('JPATH_SITE',          JPATH_BASE);
define('JPATH_THEMES',        JPATH_BASE . '/www');
define('JPATH_TEMPLATES',     JPATH_BASE . '/templates');

// Load the Composer autoloader
require JPATH_BASE . '/vendor/autoload.php';

// Load the Joomla Framework
require JPATH_BASE . '/vendor/joomla/framework/src/import.php';

try
{
	// Instantiate the application.
	$application = new JTracker\Application\TrackerApplication;

	// Execute the application.
	$application->execute();
}
catch (\Exception $e)
{
	echo get_class($e) . ': ' . $e->getMessage();

	if (defined('JDEBUG') && JDEBUG)
	{
		echo '<pre>' . str_replace(JPATH_BASE, 'JROOT', $e->getTraceAsString()) . '</pre>';
	}
}
