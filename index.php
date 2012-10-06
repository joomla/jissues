<?php
/**
 * @package    BabDev.Tracker
 *
 * @copyright  Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// Set error reporting for development
error_reporting(32767);

/**
 * Constant that is checked in included files to prevent direct access.
 */
const _JEXEC = 1;

// Bootstrap the application
require_once __DIR__ . '/application/bootstrap.php';

// Get the application
$app = JApplicationWeb::getInstance('TrackerApplicationWeb');

// Execute the application
$app->execute();
