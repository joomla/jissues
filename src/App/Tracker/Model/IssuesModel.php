<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Tracker\Model;

use Joomla\Database\DatabaseQuery;
use Joomla\Registry\Registry;
use Joomla\String\String;

use JTracker\Model\AbstractTrackerListModel;
use JTracker\Container;

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
		$query->select('s.status AS status_title, s.closed AS closed_status');
		$query->join('LEFT', '#__status AS s ON a.status = s.id');

		$filter = $this->state->get('filter.project');

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
			$query->where('(' . $db->quoteName('a.title') . ' LIKE ' . $filter . ' OR ' . $db->quoteName('a.description') . ' LIKE ' . $filter . ')');
		}

		$filter = $this->state->get('filter.status');

		if ($filter)
		{
			$query->where($db->quoteName('a.status') . ' = ' . (int) $filter);
		}

		$filter = $this->state->get('filter.priority');

		if ($filter)
		{
			$query->where($db->quoteName('a.priority') . ' = ' . (int) $filter);
		}

		// TODO: Implement filtering and join to other tables as added

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
		$id .= ':' . $this->state->get('filter.status');
		$id .= ':' . $this->state->get('filter.search');

		return parent::getStoreId($id);
	}

	/**
	 * Load the model state.
	 *
	 * @return  Registry  The state object.
	 *
	 * @since   1.0
	 */
	protected function loadState()
	{
		/* @type \JTracker\Application $application */
		$application = Container::retrieve('app');

		$projectId = $application->getProject()->project_id;

		$this->state = new Registry;

		$this->state->set('filter.project', $projectId);

		$sort = $application->getUserStateFromRequest('project_' . $projectId . '.filter.sort', 'filter-sort', 0, 'uint');

		switch ($sort)
		{
			case 1:
				$this->state->set('list.ordering', 'a.issue_number');
				$this->state->set('list.direction', 'ASC');
				break;

			case 2:
				$this->state->set('list.ordering', 'a.modified_date');
				$this->state->set('list.direction', 'DESC');
				break;

			case 3:
				$this->state->set('list.ordering', 'a.modified_date');
				$this->state->set('list.direction', 'ASC');
				break;

			default:
				$this->state->set('list.ordering', 'a.issue_number');
				$this->state->set('list.direction', 'DESC');
		}

		$this->state->set('filter.sort', $sort);

		$priority = $application->getUserStateFromRequest('project_' . $projectId . '.filter.priority', 'filter-priority', 0, 'uint');
		$this->state->set('filter.priority', $priority);

		$status = $application->getUserStateFromRequest('project_' . $projectId . '.filter.status', 'filter-status', 1, 'uint');
		$this->state->set('filter.status', $status);

		$search = $application->getUserStateFromRequest('project_' . $projectId . '.filter.search', 'filter-search', '', 'string');
		$this->state->set('filter.search', $search);

		// List state information.
		parent::loadState();
	}
}
