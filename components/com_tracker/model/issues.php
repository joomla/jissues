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
		// Populate the state object
		$this->populateState();

		// Load the query for the list
		$query = $this->getListQuery();

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

		$query->select('a.*');
		$query->from($db->quoteName('#__issues', 'a'));

		// Join over the status.
		$query->select('s.status AS status_title, s.closed AS closed_status');
		$query->join('LEFT', '#__status AS s ON a.status = s.id');

		$filter = $this->state->get('list.filter');

		if ($filter)
		{
			// Clean filter variable
			$filter = $db->quote('%' . $db->escape(JString::strtolower($filter), true) . '%', false);

			// Check the author, title, and publish_up fields
			$query->where('(' . $db->quoteName('a.title') . ' LIKE ' . $filter . ' OR ' . $db->quoteName('a.description') . ' LIKE ' . $filter . ')');
		}

		$status = $this->state->get('filter.status');
		if ($status)
		{
			$query->where($db->quoteName('a.status') . ' = ' . (int) $status);
		}

		// TODO: Implement filtering and join to other tables as added

		$ordering  = $db->escape($this->state->get('list.ordering', 'a.id'));
		$direction = $db->escape($this->state->get('list.direction', 'ASC'));
		$query->order($ordering . ' ' . $direction);

		return $query;
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * @return	void
	 * @since	1.0
	 */
	protected function populateState()
	{
		$app = JFactory::getApplication();

		// List state information
		$value = $app->input->get('limit', $app->getCfg('list_limit', 0), 'uint');
		$this->state->set('list.limit', $value);

		$value = $app->input->get('limitstart', 0, 'uint');
		$this->state->set('list.start', $value);

		$orderCol = $app->input->get('filter_order', 'a.id');
		$this->state->set('list.ordering', $orderCol);

		$listOrder = $app->input->get('filter_order_Dir', 'ASC');
		if (!in_array(strtoupper($listOrder), array('ASC', 'DESC', '')))
		{
			$listOrder = 'ASC';
		}
		$this->state->set('list.direction', $listOrder);

		$priority = $app->input->get('priority', 3, 'uint');
		$this->state->set('filter.priority', $priority);

		$status = $app->input->get('status', 0, 'uint');
		$this->state->set('filter.status', $status);

		// Optional filter text
		$this->state->set('list.filter', $app->input->get('filter-search', '', 'string'));
	}
}
