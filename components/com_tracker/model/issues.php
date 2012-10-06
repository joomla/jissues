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
 * Model to get data for the issue list view
 *
 * @package     BabDev.Tracker
 * @subpackage  Model
 * @since       1.0
 */
class TrackerModelIssues extends JModelDatabase
{
	/**
	 * Method to get an array of data items.
	 *
	 * @return  mixed  An array of data items on success, false on failure.
	 *
	 * @since   1.0
	 */
	public function getItems()
	{
		// Load the query for the list
		$query = $this->getListQuery();

		try
		{
			$this->db->setQuery($query);
			$items = $this->db->loadObjectList();
		}
		catch (RuntimeException $e)
		{
			$this->setError($e->getMessage());
			return false;
		}

		return $items;
	}

	/**
	 * Method to get a JDatabaseQuery object for retrieving the data set from a database.
	 *
	 * @return  JDatabaseQuery   A JDatabaseQuery object to retrieve the data set.
	 *
	 * @since   1.0
	 */
	protected function getListQuery()
	{
		$db    = $this->getDb();
		$query = $db->getQuery(true);

		$query->select($db->quoteName(array('a.id', 'a.gh_id', 'a.title', 'a.description', 'a.priority', 'a.status', 'a.opened', 'a.closed', 'a.modified')));
		$query->from($db->quoteName('#__issues', 'a'));

		// Join over the status.
		$query->select('s.status AS status_title, s.closed AS closed_status');
		$query->join('LEFT', '#__status AS s ON a.status = s.id');

		// TODO: Implement filtering and join to other tables as added

		$query->order('a.id ASC');

		return $query;
	}
}
