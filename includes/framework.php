<?php
/**
 * @package    JTracker
 *
 * @copyright  Copyright (C) 2012 Open Source Matters. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/*
 * Joomla system checks.
 */

ini_set('display_errors', true);
const JDEBUG = false;
@ini_set('magic_quotes_runtime', 0);

/*
 * Joomla system startup.
 */

// Import the application libraries, load it before the rest of the Platform to allow class overrides
require_once JPATH_LIBRARIES . '/tracker.php';

// Import the Joomla Platform with legacy support.
require_once JPATH_LIBRARIES . '/import.legacy.php';

// Import the CMS libraries.
require_once JPATH_LIBRARIES . '/cms.php';
