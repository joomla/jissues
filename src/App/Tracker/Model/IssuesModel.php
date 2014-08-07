<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Model;

use App\Projects\TrackerProject;

use Joomla\Database\DatabaseQuery;
use Joomla\String\String;

use JTracker\Model\AbstractTrackerListModel;

/**
 * Model to get data for the issue list view
 *
 * @since  1.0
 */
class IssuesModel extends AbstractTrackerListModel
{
	/**
	 * Context string for the model type.  This is used to handle uniqueness
	 * when dealing with the getStoreId() method and caching data structures.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $context = 'tracker.issues';

	/**
	 * Project object
	 *
	 * @var    TrackerProject
	 * @since  1.0
	 */
	protected $project = null;

	/**
	 * Get the project.
	 *
	 * @return  \App\Projects\TrackerProject
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	public function getProject()
	{
		if (is_null($this->project))
		{
			throw new \RuntimeException('Project not set');
		}

		return $this->project;
	}

	/**
	 * Set the project.
	 *
	 * @param   TrackerProject  $project  The project.
	 *
	 * @return  $this  Method supports chaining
	 *
	 * @since   1.0
	 */
	public function setProject(TrackerProject $project)
	{
		$this->project = $project;

		return $this;
	}

	/**
	 * Method to get a DatabaseQuery object for retrieving the data set from a database.
	 *
	 * @return  DatabaseQuery  A DatabaseQuery object to retrieve the data set.
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

		$filter = $this->getProject()->project_id;

		if ($filter)
		{
			$query->where($db->quoteName('a.project_id') . ' = ' . (int) $filter);
		}

		$filter = $this->state->get('filter.search');

		if ($filter)
		{
			// Clean filter variable
			$filter = $db->quote('%' . $db->escape(String::strtolower($filter), true) . '%', false);

			// Check the author, title, and publish_up fields
			$query->where(
				'(' . $db->quoteName('a.title') . ' LIKE ' . $filter
				. ' OR ' . $db->quoteName('a.description') . ' LIKE ' . $filter
				. ' OR ' . $db->quoteName('a.issue_number') . ' LIKE ' . $filter . ')');
		}

		$filter = $this->state->get('filter.status');

		if ($filter)
		{
			$query->where($db->quoteName('a.status') . ' = ' . (int) $filter);
		}

		$filter = $this->state->get('filter.state');

		if (is_numeric($filter))
		{
			$query->where($db->quoteName('s.closed') . ' = ' . (int) $filter);
		}

		$filter = $this->state->get('filter.priority');

		if ($filter)
		{
			$query->where($db->quoteName('a.priority') . ' = ' . (int) $filter);
		}

		$filter = $this->state->get('filter.user');

		if ($filter && is_numeric($filter))
		{
			$username = $this->state->get('username');

			switch ($filter)
			{
				case 1:
					$query->where($db->quoteName('a.opened_by') . ' = ' . $db->quote($username));
					break;

				case 2:
					// Join over the activities.
					$query->join('LEFT', '#__activities AS ac ON a.issue_number = ac.issue_number');
					$query->where($db->quoteName('ac.user') . ' = ' . $db->quote($username));
					$query->where($db->quoteName('ac.project_id') . ' = ' . (int) $this->getProject()->project_id);
					$query->group('a.issue_number');
					break;
			}
		}

		$ordering  = $db->escape($this->state->get('list.ordering', 'a.issue_number'));
		$direction = $db->escape($this->state->get('list.direction', 'DESC'));
		$query->order($ordering . ' ' . $direction);

		return $query;
	}

	/**
	 * Method to get a DatabaseQuery object for retrieving the data set from a database for ajax request.
	 *
	 * @return DatabaseQuery
	 *
	 * @since 1.0
	 */
	protected function getAjaxListQuery()
	{
		$db    = $this->getDb();
		$query = $db->getQuery(true);

		$query->select("a.priority, a.issue_number, a.title, a.foreign_number, a.opened_date, a.closed_date, a.modified_date, a.labels, a.merge_state");
		$query->from($db->quoteName('#__issues', 'a'));

		// Join over the status.
		$query->select('s.status AS status_title, s.closed AS closed_status');
		$query->join('LEFT', '#__status AS s ON a.status = s.id');

		$filter = $this->getProject()->project_id;

		if ($filter)
		{
			$query->where($db->quoteName('a.project_id') . ' = ' . (int) $filter);
		}

		$filter = $this->state->get('filter.search');

		if ($filter)
		{
			// Clean filter variable
			$filter = $db->quote('%' . $db->escape(String::strtolower($filter), true) . '%', false);

			// Check the author, title, and publish_up fields
			$query->where(
				'(' . $db->quoteName('a.title') . ' LIKE ' . $filter
				. ' OR ' . $db->quoteName('a.description') . ' LIKE ' . $filter
				. ' OR ' . $db->quoteName('a.issue_number') . ' LIKE ' . $filter . ')');
		}

		$filter = $this->state->get('filter.status');

		if ($filter)
		{
			$query->where($db->quoteName('a.status') . ' = ' . (int) $filter);
		}

		$filter = $this->state->get('filter.state');

		if (is_numeric($filter))
		{
			$query->where($db->quoteName('s.closed') . ' = ' . (int) $filter);
		}

		$filter = $this->state->get('filter.priority');

		if ($filter)
		{
			$query->where($db->quoteName('a.priority') . ' = ' . (int) $filter);
		}

		$filter = $this->state->get('filter.user');

		if ($filter && is_numeric($filter))
		{
			$username = $this->state->get('username');

			switch ($filter)
			{
				case 1:
					$query->where($db->quoteName('a.opened_by') . ' = ' . $db->quote($username));
					break;

				case 2:
					// Join over the activities.
					$query->join('LEFT', '#__activities AS ac ON a.issue_number = ac.issue_number');
					$query->where($db->quoteName('ac.user') . ' = ' . $db->quote($username));
					$query->where($db->quoteName('ac.project_id') . ' = ' . (int) $this->getProject()->project_id);
					$query->group('a.issue_number');
					break;
			}
		}

		$filter = $this->state->get('filter.category');

		if ($filter && is_numeric($filter))
		{
			$categoryModel = new CategoryModel($db);
			$issues        = $categoryModel->getIssueIdsByCategory($filter);

			if ($issues != null)
			{
				$issueId = array();

				foreach ($issues as $issue)
				{
					$issueId[] = $issue->issue_id;
				}

				$issueId = implode(', ', $issueId);
			}
			else
			{
				$issueId = 0;
			}

			$query->where($db->quoteName('a.id') . ' IN (' . $issueId . ')');
		}

		$ordering  = $db->escape($this->state->get('list.ordering', 'a.issue_number'));
		$direction = $db->escape($this->state->get('list.direction', 'DESC'));
		$query->order($ordering . ' ' . $direction);

		return $query;
	}

	/**
	 * Method to get a store id based on the model configuration state.
	 *
	 * This is necessary because the model is used by the component and
	 * different modules that might need different sets of data or different
	 * ordering requirements.
	 *
	 * @param   string  $id  An identifier string to generate the store id.
	 *
	 * @return  string  A store id.
	 *
	 * @since   1.0
	 */
	protected function getStoreId($id = '')
	{
		// Add the list state to the store id.
		$id .= ':' . $this->state->get('filter.priority');
		$id .= ':' . $this->state->get('filter.state');
		$id .= ':' . $this->state->get('filter.status');
		$id .= ':' . $this->state->get('filter.search');
		$id .= ':' . $this->state->get('filter.user');
		$id .= ':' . $this->state->get('filter.category');

		return parent::getStoreId($id);
	}

	/**
	 * Method to get an array of data items for ajax requests
	 *
	 * @return mixed  An array of data items on success, false on failure.
	 *
	 * @since 1.0
	 */
	public function getAjaxItems()
	{
		$store = $this->getStoreID();

		if (isset($this->cache[$store]))
		{
			return $this->cache[$store];
		}

		$query = $this->_getAjaxListQuery();

		$items = $this->_getList($query, $this->getStart(), $this->state->get('list.limit'));

		// Add the items to the internal cache.
		$this->cache[$store] = $items;

		return $this->cache[$store];
	}

	/**
	 * Method to cache the last query constructed for ajax request.
	 *
	 * This method ensures that the query is constructed only once for a given state of the model.
	 *
	 * @return  DatabaseQuery  A DatabaseQuery object
	 *
	 * @since   1.0
	 */
	protected function _getAjaxListQuery()
	{
		// Capture the last store id used.
		static $lastStoreId;

		// Compute the current store id.
		$currentStoreId = $this->getStoreId();

		// If the last store id is different from the current, refresh the query.
		if ($lastStoreId != $currentStoreId || empty($this->query))
		{
			$lastStoreId = $currentStoreId;
			$this->query = $this->getAjaxListQuery();
		}

		return $this->query;
	}

	/**
	 * Override method to get the total number of items for the data set.
	 *
	 * @return  integer  The total number of items available in the data set.
	 *
	 * @since   1.0
	 */
	public function getTotal()
	{
		// Get a storage key.
		$store = $this->getStoreId('getTotal');

		// Try to load the data from internal storage.
		if (isset($this->cache[$store]))
		{
			return $this->cache[$store];
		}

		// Load the total.
		$query = $this->_getAjaxListQuery();

		$total = (int) $this->_getListCount($query);

		// Add the total to the internal cache.
		$this->cache[$store] = $total;

		return $this->cache[$store];
	}
}
