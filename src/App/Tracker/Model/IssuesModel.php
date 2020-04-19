<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Model;

use Joomla\Database\DatabaseQuery;
use Joomla\String\StringHelper;

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
		$query->select('s.closed AS closed_status');
		$query->join('LEFT', '#__status AS s ON a.status = s.id');

		// Join over the milestones table
		$query->select('m.title AS milestone_title');
		$query->join('LEFT', '#__tracker_milestones AS m ON m.milestone_id = a.milestone_id');

		// Process the state's filters
		$query = $this->processStateFilter($query);

		$ordering  = $db->escape($this->state->get('list.ordering', 'a.issue_number'));
		$direction = $db->escape($this->state->get('list.direction', 'DESC'));
		$query->order($ordering . ' ' . $direction);

		return $query;
	}

	/**
	 * Method to get a DatabaseQuery object for retrieving the data set from a database for ajax request.
	 *
	 * @return  DatabaseQuery
	 *
	 * @since   1.0
	 */
	protected function getAjaxListQuery()
	{
		$db    = $this->getDb();
		$query = $db->getQuery(true);

		$query->select(
			'a.id, a.priority, a.issue_number, a.title, a.foreign_number, a.opened_date, a.status,
			a.closed_date, a.modified_date, a.labels, a.merge_state, a.opened_by, a.is_draft'
		);
		$query->from($db->quoteName('#__issues', 'a'));

		// Join over the status.
		$query->select('s.closed AS closed_status');
		$query->join('LEFT', '#__status AS s ON a.status = s.id');

		// Join over the users
		$query->select('u.id AS user_id');
		$query->leftJoin('#__users AS u ON a.opened_by = u.username');

		// Join over the milestones table
		$query->select('m.title AS milestone_title');
		$query->join('LEFT', '#__tracker_milestones AS m ON m.milestone_id = a.milestone_id');

		// Process the state's filters
		$query = $this->processStateFilter($query);

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
		$id .= ':' . $this->state->get('filter.created_by');
		$id .= ':' . $this->state->get('filter.category');
		$id .= ':' . $this->state->get('filter.label');
		$id .= ':' . $this->state->get('filter.tests');
		$id .= ':' . $this->state->get('filter.easytest');

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
	public function getTotal(): int
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

		/**
		 * This filter needs a GROUP BY clause,
		 * so we should create a subquery to get the correct number of rows
		 */
		$filter = $this->state->get('filter.tests');

		if ($filter && is_numeric($filter))
		{
			$subQuery = clone $query;
			$subQuery->clear('order');

			$db = $this->getDb();

			$newQuery = $db->getQuery(true)
				->select('COUNT(*)')
				->from($subQuery, 'tbl');

			$this->db->setQuery($newQuery);
			$total = (int) $this->db->loadResult();
		}
		else
		{
			$total = (int) $this->_getListCount($query);
		}

		// Add the total to the internal cache.
		$this->cache[$store] = $total;

		return $this->cache[$store];
	}

	/**
	 * Common function to process the search filter for a query
	 *
	 * @param   DatabaseQuery  $query   DatabaseQuery object
	 * @param   string         $filter  Filter string
	 *
	 * @return  DatabaseQuery
	 *
	 * @since   1.0
	 */
	private function processSearchFilter(DatabaseQuery $query, $filter)
	{
		$db = $this->getDb();

		// Clean filter variable
		$filter = $db->quote('%' . $db->escape(StringHelper::strtolower($filter), true) . '%', false);

		// Check the author, title, and publish_up fields
		$query->where(
			'(' . $db->quoteName('a.title') . ' LIKE ' . $filter
			. ' OR ' . $db->quoteName('a.description') . ' LIKE ' . $filter
			. ' OR ' . $db->quoteName('a.issue_number') . ' LIKE ' . $filter . ')'
		);

		return $query;
	}

	/**
	 * Common function to process the filters for a query based on the model state
	 *
	 * @param   DatabaseQuery  $query  DatabaseQuery object
	 *
	 * @return  DatabaseQuery
	 *
	 * @since   1.0
	 */
	private function processStateFilter(DatabaseQuery $query)
	{
		$db = $this->getDb();

		$filter = $this->getProject()->project_id;

		if ($filter)
		{
			$query->where($db->quoteName('a.project_id') . ' = ' . (int) $filter);
		}

		$filter = $this->state->get('filter.search');

		if ($filter)
		{
			$query = $this->processSearchFilter($query, $filter);
		}

		$statusFilter = $this->state->get('filter.status');

		if ($statusFilter)
		{
			$query->where($db->quoteName('a.status') . ' = ' . (int) $statusFilter);
		}

		$stateFilter = $this->state->get('filter.state');

		// State == 2 means "all".
		if (is_numeric($stateFilter) && 2 != $stateFilter)
		{
			$query->where($db->quoteName('s.closed') . ' = ' . (int) $stateFilter);
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

		$filter = $this->state->get('filter.created_by');

		if ($filter)
		{
			// Clean filter variable
			$filter = $db->quote('%' . $db->escape(StringHelper::strtolower($filter), true) . '%', false);

			$query->where($db->quoteName('a.opened_by') . ' LIKE ' . $filter);
		}

		$filter = $this->state->get('filter.category');

		if ($filter && is_numeric($filter))
		{
			$categoryModel = new CategoryModel($db);

			// If the category filter equals -1, that means we want issues without category.
			if ($filter == -1)
			{
				$issues = $categoryModel->getIssueIdsWithCategory();
			}
			else
			{
				$issues = $categoryModel->getIssueIdsByCategory($filter);
			}

			if ($issues != null)
			{
				$issueId = [];

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

			// Handle the no category filter
			if ($filter == -1)
			{
				$query->where($db->quoteName('a.id') . ' NOT IN (' . $issueId . ')');
			}
			else
			{
				$query->where($db->quoteName('a.id') . ' IN (' . $issueId . ')');
			}
		}

		$filter = $this->state->get('filter.label');

		if ($filter && is_numeric($filter))
		{
			$query->where('FIND_IN_SET(' . $filter . ', ' . $db->quoteName('a.labels') . ')');
		}

		$filter = $this->state->get('filter.tests');

		if ($filter && is_numeric($filter))
		{
			// Common query elements
			$query
				->leftJoin($db->quoteName('#__issues_tests', 'it') . ' ON a.id = it.item_id')
				->where($db->quoteName('a.has_code') . ' = 1')
				->where('(' . $db->quoteName('it.sha') . ' = ' . $db->quoteName('a.pr_head_sha') . ' OR ' . $db->quoteName('it.sha') . ' IS NULL)')
				->group('a.issue_number');

			// We can only reliably set this WHERE clause if the status and state filters are not set
			if (!$statusFilter && !is_numeric($stateFilter))
			{
				$query->where($db->quoteName('s.closed') . ' = 0');
			}

			switch ($filter)
			{
				case 1:
					$query
						->where($db->quoteName('it.result') . ' = 1')
						->having('COUNT(it.item_id) = 1');
					break;

				case 2:
					$query
						->where($db->quoteName('it.result') . ' = 1')
						->having('COUNT(it.item_id) > 1');
					break;

				case 3:
					$query
						->having('COUNT(it.item_id) = 0');
					break;
			}
		}

		$filter = $this->state->get('filter.easytest');

		if (is_numeric($filter) && $filter < 2)
		{
			$query->where($db->quoteName('a.easy') . (0 == $filter ? ' != ' : ' = ') . '1');
		}

		$filter = (int) $this->state->get('filter.type');

		if ($filter)
		{
			// 1 - PR
			// 2 - Issue
			$query->where($db->quoteName('a.has_code') . ' = ' . (2 == $filter ? 0 : 1));
		}

		$filter = (int) $this->state->get('filter.milestone');

		if ($filter)
		{
			$query->where($db->quoteName('a.milestone_id') . ' = ' . $filter);
		}

		return $query;
	}
}
