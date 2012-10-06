<?php
/**
 * @package     BabDev.Tracker
 * @subpackage  Application
 *
 * @copyright   Copyright (C) 2012 Michael Babker. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

// Define the base path and require the other defines
define('JPATH_BASE', dirname(__DIR__));
require_once JPATH_BASE . '/application/defines.php';

// Launch the application
require_once JPATH_BASE . '/application/framework.php';

// Register the Tracker application
JLoader::registerPrefix('Tracker', JPATH_BASE);
