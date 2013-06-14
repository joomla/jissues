<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Groups\Model;

use App\Groups\Table\GroupsTable;

use Joomla\Database\DatabaseQuery;
use Joomla\Factory;

use JTracker\Model\AbstractTrackerListModel;

/**
 * Model to get data for the projects list view
 *
 * @package  JTracker\Components\Tracker
 * @since    1.0
 */
class GroupsModel extends AbstractTrackerListModel
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
		$projectId = Factory::$application->getProject()->project_id;

		$db    = $this->getDb();
		$query = $db->getQuery(true);

		$table = new GroupsTable($db);

		$query->select('a.*');
		$query->from($db->quoteName($table->getTableName(), 'a'));
		$query->where($db->quoteName('project_id') . ' = ' . (int) $projectId);

		return $query;
	}
}
