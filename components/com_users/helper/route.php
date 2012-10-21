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
 * Users Route Helper
 *
 * @package     JTracker
 * @subpackage  com_users
 * @since       1.0
 */
abstract class UsersHelperRoute
{
	/**
	 * Method to get the menu items for the component.
	 *
	 * @return  array  An array of menu items.
	 *
	 * @since   1.0
	 */
	public static function &getItems()
	{
		static $items;

		// Get the menu items for this component.
		if (!isset($items))
		{
			// Include the site app in case we are loading this from the admin.
			//require_once JPATH_SITE.'/includes/application.php';

			$app	= JFactory::getApplication('site');
			$menu	= $app->getMenu();
			$com	= JComponentHelper::getComponent('com_users');
			$items	= $menu->getItems('component_id', $com->id);

			// If no items found, set to empty array.
			if (!$items)
			{
				$items = array();
			}
		}

		return $items;
	}

	/**
	 * Method to get the route for the given view
	 *
	 * @param   string  $view  The view to retrieve the route for
	 *
	 * @return  mixed  Integer menu id on success, null on failure.
	 *
	 * @since   1.0
	 */
	protected static function getRoute($view)
	{
		// Get the items.
		$items	= self::getItems();
		$itemid	= null;

		// Search for a suitable menu id.
		foreach ($items as $item)
		{
			if (isset($item->query['view']) && $item->query['view'] === $view)
			{
				$itemid = $item->id;
				break;
			}
		}

		return $itemid;
	}

	/**
	 * Method to get a route configuration for the login view.
	 *
	 * @return  mixed  Integer menu id on success, null on failure.
	 *
	 * @since   1.0
	 */
	public static function getLoginRoute()
	{
		return self::getRoute('login');
	}

	/**
	 * Method to get a route configuration for the profile view.
	 *
	 * @return  mixed  Integer menu id on success, null on failure.
	 *
	 * @since   1.0
	 */
	public static function getProfileRoute()
	{
		return self::getRoute('profile');
	}

	/**
	 * Method to get a route configuration for the registration view.
	 *
	 * @return  mixed  Integer menu id on success, null on failure.
	 *
	 * @since   1.0
	 */
	public static function getRegistrationRoute()
	{
		return self::getRoute('registration');
	}

	/**
	 * Method to get a route configuration for the remind view.
	 *
	 * @return  mixed  Integer menu id on success, null on failure.
	 *
	 * @since   1.0
	 */
	public static function getRemindRoute()
	{
		return self::getRoute('remind');
	}

	/**
	 * Method to get a route configuration for the resend view.
	 *
	 * @return  mixed  Integer menu id on success, null on failure.
	 *
	 * @since   1.0
	 */
	public static function getResendRoute()
	{
		return self::getRoute('resend');
	}

	/**
	 * Method to get a route configuration for the reset view.
	 *
	 * @return  mixed  Integer menu id on success, null on failure.
	 *
	 * @since   1.0
	 */
	public static function getResetRoute()
	{
		return self::getRoute('reset');
	}
}
