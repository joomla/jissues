<?php
/**
 * @package     BabDev.Tracker
 * @subpackage  Model
 *
 * @copyright   Copyright (C) 2012 Michael Babker. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Model to get data for the issue detail view
 *
 * @package     BabDev.Tracker
 * @subpackage  Model
 * @since       1.0
 */
class TrackerModelIssue extends JModelDatabase
{
	/**
	 * Method to get the comments for an item.
	 *
	 * @param   integer  $pk  The id of the primary key.
	 *
	 * @return  mixed  An array of data items on success, false on failure.
	 *
	 * @since   1.0
	 */
	public function getComments($id)
	{
		$db    = $this->getDb();
		$query = $db->getQuery(true);

		$query->select('*');
		$query->from($db->quoteName('#__issue_comments', 'a'));
		$query->where($db->quoteName('a.issue_id') . ' = ' . (int) $id);

		try
		{
			$this->db->setQuery($query);
			$items = $this->db->loadObjectList();
		}
		catch (RuntimeException $e)
		{
			JFactory::getApplication()->enqueueMessage($e->getMessage(), 'error');
			return false;
		}

		return $items;
	}

	/**
	 * Method to get a single record.
	 *
	 * @param   integer  $pk  The id of the primary key.
	 *
	 * @return  mixed  Object on success, false on failure.
	 *
	 * @since   1.0
	 */
	public function getItem($id)
	{
		// load the row using JTable and getInstance.
		$table = JTable::getInstance('Issue');

		if ($id > 0)
		{
			// Attempt to load the row.
			$return = $table->load($id);

			// Check for a table object error.
			if ($return === false && $table->getError())
			{
				JFactory::getApplication()->enqueueMessage($table->getError(), 'error');
				return false;
			}
		}

		// Convert to the JObject before adding other data.
		$properties = $table->getProperties(1);
		$item = JArrayHelper::toObject($properties, 'JObject');

		return $item;
	}
}
