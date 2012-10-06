<?php
/**
 * @package     BabDev.Tracker
 * @subpackage  com_tracker
 *
 * @copyright   Copyright (C) 2012 Michael Babker. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

// Register the component with the autoloader
JLoader::registerPrefix('Tracker', JPATH_COMPONENT_SITE);

// Get the task from the input object
$task = JFactory::getApplication()->input->get('task', null);

if (is_null($task))
{
	$task = 'default';
}

$cClass = 'TrackerController' . ucfirst($task);
$controller = new $cClass;
$controller->execute();
