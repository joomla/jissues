<?php
/**
 * @package     JTracker
 * @subpackage  com_tracker
 *
 * @copyright   Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

JToolbarHelper::title('JTracker');

if (!JFactory::getUser()->authorise('core.manage', 'com_tracker'))
{
	JFactory::getApplication()->enqueueMessage(JText::_('JERROR_ALERTNOAUTHOR'), 404);

	return;
}

JLoader::register('TrackerHelper', __DIR__ . '/helpers/tracker.php');

TrackerHelper::addSubmenu(JFactory::getApplication()->input->get('view'));

JHTML::_('addIncludePath', JPATH_LIBRARIES . '/tracker/html');

$controller = JControllerLegacy::getInstance('Tracker');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();
