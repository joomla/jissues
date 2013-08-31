<?php
/**
 * Unit test runner bootstrap file for the Joomla Framework.
 *
 * @copyright  Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 * @link       http://www.phpunit.de/manual/current/en/installation.html
 */

// Fix magic quotes.
@ini_set('magic_quotes_runtime', 0);

// Maximise error reporting.
error_reporting(-1);
ini_set('display_errors', 1);

/*
 * Ensure that required path constants are defined.
 */
define('JPATH_TESTS',  realpath(__DIR__));
define('JPATH_ROOT',   realpath(JPATH_TESTS . '/tmp'));
define('JPATH_THEMES', JPATH_TESTS . '/themes_base');


// Register the test classes.
$loader = require __DIR__ . '/../vendor/autoload.php';

// Add the namespace for our application to the autoloader.
$loader->add('Test', __DIR__);

/*
 * The PHP garbage collector can be too aggressive in closing circular references before they are no longer needed.  This can cause
 * segfaults during long, memory-intensive processes such as testing large test suites and collecting coverage data.  We explicitly
 * disable garbage collection during the execution of PHPUnit processes so that we (hopefully) don't run into these issues going
 * forwards.  This is only a problem PHP 5.3+.
 */
gc_disable();
