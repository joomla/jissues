<?php
/**
 * @package    JTracker
 *
 * @copyright  Copyright (C) 2012 Open Source Matters. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/*
 * Joomla! Tracker Application Defines
 */

// Global definitions
$parts = explode(DIRECTORY_SEPARATOR, JPATH_BASE);

// Defines
define('JPATH_ROOT',          implode(DIRECTORY_SEPARATOR, $parts));
define('JPATH_SITE',          JPATH_ROOT);
define('JPATH_CONFIGURATION', JPATH_ROOT);
define('JPATH_ADMINISTRATOR', JPATH_ROOT . '/administrator');
define('JPATH_INSTALLATION',  JPATH_ROOT . '/installation');
define('JPATH_LIBRARIES',     JPATH_ROOT . '/libraries');
define('JPATH_CACHE',         JPATH_BASE . '/cache');
define('JPATH_THEMES',        JPATH_BASE . '/templates');
define('JPATH_PLUGINS',       JPATH_BASE . '/plugins');
