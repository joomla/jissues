<?php
/**
 * @package     BabDev.Tracker
 * @subpackage  HTML
 *
 * @copyright   Copyright (C) 2012 Michael Babker. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('JPATH_BASE') or die;

/**
 * Utility class for tracker statuses
 *
 * @package     BabDev.Tracker
 * @subpackage  HTML
 * @since       1.0
 */
abstract class JHtmlStatus
{
	/**
	 * Cached array of the status items.
	 *
	 * @var    array
	 * @since  1.0
	 */
	protected static $items = array();

	/**
	 * Returns an array of statuses.
	 *
	 * @return  array
	 *
	 * @since   1.0
	 */
	public static function options()
	{
		static $loaded;

		if (!$loaded)
		{
			$db = JFactory::getDbo();
			$query = $db->getQuery(true);

			$query->select($db->quoteName(array('id', 'status')));
			$query->from($db->quoteName('#__status'));

			$query->order('id');

			$db->setQuery($query);
			$items = $db->loadObjectList();

			// Assemble the list options.
			self::$items[$hash] = array();

			foreach ($items as $item)
			{
				self::$items[] = JHtml::_('select.option', $item->id, JText::_('COM_TRACKER_STATUS_' . strtoupper($item->status)));
			}

			$loaded = true;
		}

		return self::$items;
	}
}
