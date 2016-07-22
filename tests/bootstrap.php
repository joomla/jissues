<?php
/**
 * Unit test runner bootstrap file for the Joomla Framework.
 *
 * @copyright  Copyright (C) 2002 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 * @link       http://www.phpunit.de/manual/current/en/installation.html
 */

// Maximise error reporting.
error_reporting(-1);
ini_set('display_errors', 1);

/*
 * Ensure that required path constants are defined.
 */
defined('JPATH_ROOT') || define('JPATH_ROOT', realpath(dirname(__DIR__)));
defined('JPATH_CONFIGURATION') || define('JPATH_CONFIGURATION', JPATH_ROOT . '/etc');
defined('JPATH_THEMES') || define('JPATH_THEMES', JPATH_ROOT . '/www');
defined('JPATH_TEMPLATES') || define('JPATH_TEMPLATES', JPATH_ROOT . '/templates');

$composerPath = JPATH_ROOT . '/vendor/autoload.php';

if (!file_exists($composerPath))
{
	throw new RuntimeException('Composer is not set up, please run "composer install".');
}

require JPATH_ROOT . '/vendor/autoload.php';
