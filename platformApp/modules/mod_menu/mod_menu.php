<?php
/**
 * @package     JTracker
 * @subpackage  mod_menu
 *
 * @copyright   Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

// Get the option from the input object
$option = JFactory::getApplication()->input->getCmd('option', '');

require JModuleHelper::getLayoutPath('mod_menu', 'default');
