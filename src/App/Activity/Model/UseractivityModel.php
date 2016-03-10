<?php
/**
 * Part of the Joomla Tracker's Activity Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Activity\Model;

use Joomla\Database\DatabaseQuery;

use JTracker\Model\AbstractTrackerListModel;

/**
 * Model to get user activity data
 *
 * @since  1.0
 */
class UseractivityModel extends AbstractTrackerListModel
{
	/**
	 * Context string for the model type.  This is used to handle uniqueness
	 * when dealing with the getStoreId() method and caching data structures.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $context = 'activity.user.activity';

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
		$eventTypeSubquery = $db->getQuery(true)
			->select('event')
			->from('#__activity_types');

		// Filter out our bot users
		$filterBots = $db->getQuery(true)
			->select('DISTINCT gh_editbot_user')
			->from('#__tracker_projects');

		$codePointSubquery = $db->getQuery(true)
			->select(['id', 'issue_number', 'project_id', 'opened_by'])
			->from('#__issues')
			->where('has_code = 1')
			->where('project_id = ' . (int) $this->getProject()->project_id);

		// Select required data.
		$select = [
			'CASE WHEN u.id IS NULL THEN a.user WHEN u.name IS NULL OR u.name = ' . $db->quote('') . ' THEN u.username ELSE u.name END as name',
			'SUM(t.activity_points) + (COUNT(c.id) * 5) AS total_points',
			'SUM(CASE WHEN t.activity_group = ' . $db->quote('Tracker') . ' THEN t.activity_points ELSE 0 END) AS tracker_points',
			'SUM(CASE WHEN t.activity_group = ' . $db->quote('Test') . ' THEN t.activity_points ELSE 0 END) AS test_points',
			'(COUNT(c.id) * 5) AS code_points'
		];

		$query->select($select)
			->from('#__activities AS a')
			->join('LEFT', '#__activity_types AS t ON a.event = t.event')
			->join('LEFT', '#__users AS u ON a.user = u.username')
			->where('a.event IN (' . (string) $eventTypeSubquery . ')')
			->where('a.user NOT IN (' . (string) $filterBots . ')')
			->where('a.project_id = ' . (int) $this->getProject()->project_id);

		// Apply this date filter for both the code point subquery and the main activity query
		if ($periodValue == 'Custom')
		{
			$query->where('DATE(a.created_date) BETWEEN '
				. $db->quote($this->state->get('list.startdate'))
				. ' AND '
				. $db->quote($this->state->get('list.enddate'))
			);

			$codePointSubquery->where('DATE(opened_date) BETWEEN '
				. $db->quote($this->state->get('list.startdate'))
				. ' AND '
				. $db->quote($this->state->get('list.enddate'))
			);
		}
		else
		{
			$query->where('DATE(a.created_date) > DATE(DATE_ADD(NOW(), INTERVAL ' . $periodValue . '))');
			$codePointSubquery->where('DATE(opened_date) > DATE(DATE_ADD(NOW(), INTERVAL ' . $periodValue . '))');
		}

		// Append the code point subquery now
		$query->join(
			'LEFT',
			'(' . (string) $codePointSubquery . ') AS c ON (a.issue_number = c.issue_number AND a.project_id = c.project_id AND a.user = c.opened_by AND a.event = ' . $db->quote('open') . ')'
		);

		if (in_array($this->state->get('list.activity_type'), [1, 2]))
		{
			// This can only filter Tracker and Test activity types
			$query->where('t.activity_group = ' . $db->quote($type));
			$query->order('SUM(CASE WHEN t.activity_group = ' . $db->quote($type) . ' THEN t.activity_points ELSE 0 END) DESC, SUM(t.activity_points) + (COUNT(c.id) * 5) DESC');
		}
		elseif ($this->state->get('list.activity_type') == 3)
		{
			// Since we have to return all data, sort on the code point result first
			$query->order('(COUNT(c.id) * 5) DESC, SUM(t.activity_points) + (COUNT(c.id) * 5) DESC');
		}
		else
		{
			$query->order('SUM(t.activity_points) + (COUNT(c.id) * 5) DESC');
		}

		$query->group('a.user');

		return $query;
	}
}
