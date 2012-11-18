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
				self::$items[] = JHtml::_('select.option', $item->id, JText::_('COM_TRACKER_STATUS_' . strtoupper($item->status)));
			}

			self::$items = array(
				'<select name="jform[status]" class="inputbox" id="jform_status">',
				JHtml::_('select.options', self::$items, 'value', 'text', $active),
				'</select>'
			);

			$loaded = true;
		}

		return implode("\n", self::$items);
	}
}
