<?php
/**
 * Part of the Joomla Tracker's Activity Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Activity\Model;

use JTracker\Model\AbstractTrackerDatabaseModel;
use JTracker\Pagination\TrackerPagination;

/**
 * Model to get project activity data
 *
 * @since  1.0
 */
class ProjectActivityModel extends AbstractTrackerDatabaseModel
{
	/**
	 * Get the count of open, closed, and fixed issues for a period of time
	 *
	 * @return  object[]
	 *
	 * @since   1.0
	 */
	public function getIssueCounts()
	{
		// Create a new query object.
		$db    = $this->getDb();
		$query = $db->getQuery(true);

		$periodList  = [1 => 7, 2 => 30, 3 => 90];
		$periodNames = [1 => 'Weeks', 2 => 'Months', 3 => 'Quarters'];
		$periodName  = $periodNames[$this->state->get('list.period')];
		$periodValue = $periodList[$this->state->get('list.period')];

		// Get 12 columns
		for ($i = 4; $i > 0; $i--)
		{
			$startDay = ($i * $periodValue) - 1;
			$endDay   = ($i - 1) * $periodValue;
			$query->select(
				'SUM(CASE WHEN (DATE(i.closed_date) BETWEEN '
				. 'Date(DATE_ADD(now(), INTERVAL -' . $startDay . ' DAY)) '
				. ' AND Date(DATE_ADD(now(), INTERVAL -' . $endDay . ' DAY))) AND i.status = 5 THEN 1 ELSE 0 END)'
				. ' AS fixed' . $i
			);
		}

		for ($i = 4; $i > 0; $i--)
		{
			$startDay = ($i * $periodValue) - 1;
			$endDay   = ($i - 1) * $periodValue;
			$query->select(
				'SUM(CASE WHEN (DATE(i.closed_date) BETWEEN '
				. 'Date(DATE_ADD(now(), INTERVAL -' . $startDay . ' DAY)) '
				. ' AND Date(DATE_ADD(now(), INTERVAL -' . $endDay . ' DAY))) AND s.closed = 1 THEN 1 ELSE 0 END)'
				. ' AS closed' . $i
			);
		}

		$query->select('DATE(NOW()) AS end_date')
			->from($db->quoteName('#__issues') . ' AS i')
			->join('LEFT', '#__status AS s ON i.status = s.id')
			->where('date(i.closed_date) > Date(DATE_ADD(now(), INTERVAL -' . ($periodValue * 4) . ' DAY))')
			->where('s.closed = 1');

		$db->setQuery($query, $this->state->get('list.start'), $this->state->get('list.limit'));
		$closedIssues = $db->loadObject();

		$query = $db->getQuery(true);

		for ($i = 4; $i > 0; $i--)
		{
			$startDay = ($i * $periodValue) - 1;
			$endDay   = ($i - 1) * $periodValue;
			$query->select(
				'SUM(CASE WHEN DATE(i.opened_date) BETWEEN '
				. 'Date(DATE_ADD(now(), INTERVAL -' . $startDay . ' DAY)) '
				. ' AND Date(DATE_ADD(now(), INTERVAL -' . $endDay . ' DAY)) THEN 1 ELSE 0 END)'
				. ' AS opened' . $i
			);
		}

		$query->select('DATE(NOW()) AS end_date')
			->from($db->quoteName('#__issues') . ' AS i')
			->where('date(i.opened_date) > Date(DATE_ADD(now(), INTERVAL -' . ($periodValue * 4) . ' DAY))');

		$db->setQuery($query, $this->state->get('list.start'), $this->state->get('list.limit'));
		$openedIssues = $db->loadObject();

		return [$openedIssues, $closedIssues];
	}

	/**
	 * Set the pagination object.
	 *
	 * @param   TrackerPagination  $pagination  The pagination object.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function setPagination(TrackerPagination $pagination)
	{
		// This is just here for Controller compatibility and a bit of my laziness
	}
}
