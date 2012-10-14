<?php
/**
 * User: elkuku
 * Date: 08.10.12
 * Time: 13:46
 */

JToolbarHelper::title('JTracker');

if (!JFactory::getUser()->authorise('core.manage', 'com_tracker'))
{
	JFactory::getApplication()->enqueueMessage(JText::_('JERROR_ALERTNOAUTHOR'), 404);
	return;
}

JLoader::register('TrackerHelper', __DIR__ . '/helpers/tracker.php');

TrackerHelper::addSubmenu(JFactory::getApplication()->input->get('view'));

JHTML::_('addIncludePath', JPATH_LIBRARIES.'/tracker/html');

// "load" a class with the _() function so we can call it directly later.
JHtml::_('projects.select', 'x', 'y');

$controller	= JControllerLegacy::getInstance('Tracker');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();

