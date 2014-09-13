<?php
/**
 * Part of the Joomla Tracker's Activity Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Activity\Model;

use App\Projects\TrackerProject;

use Joomla\Database\DatabaseQuery;

use JTracker\Model\AbstractTrackerListModel;

/**
 * Model to get activity data
 *
 * @since  1.0
 */
class ActivityModel extends AbstractTrackerListModel
{
	/**
	 * Context string for the model type.  This is used to handle uniqueness
	 * when dealing with the getStoreId() method and caching data structures.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $context = 'activity.activity';

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

		$periodList  = [1 => '-7 DAY', 2 => '-30 Day', 3 => '-90 DAY', 4 => '-1 YEAR', 5 => 'Custom'];
		$periodValue = $periodList[$this->state->get('list.period')];

		$typeList = ['All', 'Tracker', 'Test', 'Code'];
		$type     = $typeList[$this->state->get('list.activity_type')];

		// Only select rows where we have activity types, build the subquery for this now
		$subquery = $db->getQuery(true)
			->select('event')
			->from('#__activity_types');

		// Filter out our bot users
		$filterBots = $db->getQuery(true)
			->select('DISTINCT gh_editbot_user')
			->from('#__tracker_projects');

		// Select required data.
		$select = [
			'a.user AS name',
			'SUM(t.activity_points) AS total_points',
			'SUM(CASE WHEN t.activity_group = ' . $db->quote('Tracker') . ' THEN t.activity_points ELSE 0 END) AS tracker_points',
			'SUM(CASE WHEN t.activity_group = ' . $db->quote('Test') . ' THEN t.activity_points ELSE 0 END) AS test_points',
			'SUM(CASE WHEN t.activity_group = ' . $db->quote('Code') . ' THEN t.activity_points ELSE 0 END) AS code_points'
		];
		$query->select($select)
			->from('#__activities AS a')
			->join('LEFT', '#__activity_types AS t ON a.event = t.event')
			->where('a.event IN (' . (string) $subquery . ')')
			->where('a.user NOT IN (' . (string) $filterBots . ')')
			->where('a.project_id = ' . (int) $this->getProject()->project_id);

		if ($periodValue == 'Custom')
		{
			$query->where('DATE(a.created_date) BETWEEN ' . $db->quote($this->state->get('list.startdate')) . ' AND ' . $db->quote($this->state->get('list.enddate')));
		}
		else
		{
			$query->where('DATE(a.created_date) > DATE(DATE_ADD(NOW(), INTERVAL ' . $periodValue . '))');
		}

		if ($this->state->get('list.activity_type') > 0)
		{
			$query->where('t.activity_group = ' . $db->quote($type));
			$query->order('SUM(CASE WHEN t.activity_group = ' . $db->quote($type) . ' THEN t.activity_points ELSE 0 END) DESC, SUM(t.activity_points) DESC');
		}
		else
		{
			$query->order('SUM(t.activity_points) DESC');
		}

		$query->group('a.user');

		return $query;
	}
}
