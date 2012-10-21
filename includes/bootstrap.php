<?php
/**
 * @package    JTracker
 *
 * @copyright  Copyright (C) 2012 Open Source Matters. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

// Define the base path and require the other defines
define('JPATH_BASE', dirname(__DIR__));
require_once __DIR__ . '/defines.php';

// Launch the application
require_once __DIR__ . '/framework.php';
