<?php
/**
 * User: elkuku
 * Date: 10.10.12
 * Time: 12:55
 */

abstract class UsersHelperSidebar
{

	public static function prepare()
	{
		$vName = JFactory::getApplication()->input->get('view');
		$user = JFactory::getUser();

		$stdViews = array('login');
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
