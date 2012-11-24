<?php
/**
 * @package     JTracker
 * @subpackage  HTML
 *
 * @copyright   Copyright (C) 2012 Open Source Matters. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('JPATH_PLATFORM') or die;

/**
 * Utility class for tracker statuses
 *
 * @package     JTracker
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
	 * Returns an array of statuses for a filter list.
	 *
	 * @return  array
	 *
	 * @since   1.0
	 */
	public static function filter()
	{
		static $loaded;

		if (!$loaded)
		{
			self::_load();

			$loaded = true;
		}

		return self::$items;
	}

	/**
	 * Returns an array of statuses.
	 *
	 * @param   integer  $active  The active item
	 *
	 * @return  array
	 *
	 * @since   1.0
	 */
	public static function options($active = null)
	{
		static $loaded;

		if (!$loaded)
		{
			self::_load();

			self::$items = array(
				'<select name="jform[status]" class="inputbox" id="jform_status">',
				JHtmlSelect::options(self::$items, 'value', 'text', $active),
				'</select>'
			);

			$loaded = true;
		}

		return implode("\n", self::$items);
	}

	/**
	 * Loads the statuses from the database
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	private static function _load()
	{
		if (empty(self::$items))
		{
			$db = JFactory::getDbo();
			$query = $db->getQuery(true);

			$query->select($db->quoteName(array('id', 'status')));
			$query->from($db->quoteName('#__status'));

			$query->order('id');

			$db->setQuery($query);
			$items = $db->loadObjectList();

			// Assemble the list options.
			self::$items = array();

			foreach ($items as $item)
			{
				self::$items[] = JHtmlSelect::option($item->id, JText::_('COM_TRACKER_STATUS_' . strtoupper($item->status)));
			}
		}
	}
}
