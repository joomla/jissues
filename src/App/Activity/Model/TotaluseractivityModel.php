<?php
/**
 * Part of the Joomla Tracker's Activity Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Activity\Model;

use JTracker\Model\AbstractTrackerDatabaseModel;

/**
 * Model to get the total user activity data
 *
 * @since  1.0
 */
class TotaluseractivityModel extends AbstractTrackerDatabaseModel
{
	/**
	 * Get the total activity points
	 *
	 * @return  object[]
	 *
	 * @since   1.0
	 */
	public function getTotalActivity()
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
			't.activity_group',
			'DATE(NOW()) AS end_date'
		];

		$periodList  = [1 => 7, 2 => 30, 3 => 90];
		$periodValue = $periodList[$this->state->get('list.period')];

		// Get 12 columns
		for ($i = 4; $i > 0; $i--)
		{
			$startDay = ($i * $periodValue) - 1;
			$endDay   = ($i - 1) * $periodValue;
			$query->select(
				'SUM(CASE WHEN DATE(a.created_date) BETWEEN '
				. 'DATE(DATE_ADD(now(), INTERVAL -' . $startDay . ' DAY)) '
				. ' AND DATE(DATE_ADD(now(), INTERVAL -' . $endDay . ' DAY)) THEN t.activity_points ELSE 0 END)'
				. ' AS p' . $i
			);
		}

		$query->select($select)
			->from('#__activities AS a')
			->join('LEFT', '#__activity_types AS t ON a.event = t.event')
			->where('a.event IN (' . (string) $eventTypeSubquery . ')')
			->where('a.user NOT IN (' . (string) $filterBots . ')')
			->where('a.project_id = ' . (int) $this->getProject()->project_id)
			->where('DATE(a.created_date) > DATE(DATE_ADD(now(), INTERVAL -' . ($periodValue * 4) . ' DAY))')
			->order('t.activity_group DESC');

		// Append the code point subquery now
		$query->join(
			'LEFT',
			'(' . (string) $codePointSubquery
				. ') AS c ON (a.issue_number = c.issue_number AND a.project_id = c.project_id AND a.user = c.opened_by AND a.event = '
				. $db->quote('open') . ')'
		);

		$query->group('t.activity_group');

		if (in_array($this->state->get('list.activity_type'), [1, 2]))
		{
			// This can only filter Tracker and Test activity types
			$query->where('t.activity_group = ' . $db->quote($type));
		}
		elseif ($this->state->get('list.activity_type') == 3)
		{
			// Since we have to return all data, sort on the code point result first
			$query->having('COUNT(c.id) > 0');
		}

		$db->setQuery($query);

		return $db->loadObjectList();
	}
}
