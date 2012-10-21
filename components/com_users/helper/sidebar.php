<?php
/**
 * @package     JTracker
 * @subpackage  com_users
 *
 * @copyright   Copyright (C) 2012 Open Source Matters. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Users Sidebar Helper
 *
 * @package     JTracker
 * @subpackage  com_users
 * @since       1.0
 */
abstract class UsersHelperSidebar
{
	/**
	 * Method to prepare the sidebar
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public static function prepare()
	{
		$vName = JFactory::getApplication()->input->get('view');
		$user  = JFactory::getUser();

		$stdViews    = array('login');
		$actionViews = array('reset', 'remind', 'registration');

		JHtmlSidebar::addEntry(
			JText::_('User'),
			'index.php?option=com_users',
			in_array($vName, $stdViews)
		);

		if (false == in_array($vName, $stdViews) && in_array($vName, $actionViews))
		{
			JHtmlSidebar::addEntry(
				JText::_(sprintf('User / %s', $vName)),
				'index.php?option=com_users&view=' . $vName,
				in_array($vName, $actionViews)
			);
		}

		if ($user->guest)
		{
			return;
		}

		JHtmlSidebar::addEntry(
			JText::_('View Profile'),
			'index.php?option=com_users&view=profile',
			$vName == 'profile'
		);
	}
}
