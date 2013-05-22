<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tracker\Components\Tracker\Model;

use Joomla\Database\DatabaseQuery;
use Joomla\Factory;
use Joomla\Tracker\Components\Tracker\Table\ActivitiesTable;
use Joomla\Tracker\Model\AbstractTrackerListModel;

/**
 * Class ActivitiesModel.
 *
 * @since  1.0
 */
class ActivitiesModel extends AbstractTrackerListModel
{
	/**
	 * Method to get a DatabaseQuery object for retrieving the data set from a database.
	 *
	 * @return  DatabaseQuery   A DatabaseQuery object to retrieve the data set.
	 *
	 * @since   1.0
	 */
	protected function getListQuery()
	{
		/* @type \Joomla\Tracker\Application\TrackerApplication $application */
		$application = Factory::$application;
		$projectId = $application->input->getInt('project_id');
		$issueId = $application->input->getInt('id');

		$db    = $this->getDb();
		$query = $db->getQuery(true);

		$table = new ActivitiesTable($db);

		$query->select('a.*');
		$query->from($db->quoteName($table->getTableName(), 'a'));
		$query->where($db->quoteName('project_id') . ' = ' . (int) $projectId);
		$query->where($db->quoteName('issue_id') . ' = ' . (int) $issueId);
		$query->order($db->quoteName('created_date'));

		$this->state->set('list.limit', 0);

		return $query;
	}
}
