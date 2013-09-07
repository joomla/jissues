<?php
/**
 * Part of the Joomla Tracker's Groups Application
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Groups\Model;

use App\Groups\Table\GroupsTable;

use Joomla\Database\DatabaseQuery;

use JTracker\Model\AbstractTrackerListModel;
use JTracker\Container;

/**
 * Model to get data for the groups list view
 *
 * @since  1.0
 */
class GroupsModel extends AbstractTrackerListModel
{
	/**
	 * Method to get a DatabaseQuery object for retrieving the data set from a database.
	 *
	 * @return  DatabaseQuery  A DatabaseQuery object to retrieve the data set.
	 *
	 * @since   1.0
	 */
	protected function getListQuery()
	{
		$app = Container::retrieve('app');
		$projectId = $app->getProject()->project_id;

		$db    = $this->getDb();
		$query = $db->getQuery(true);

		$table = new GroupsTable($db);

		$query->select('a.*')
			->from($db->quoteName($table->getTableName(), 'a'))
			->where($db->quoteName('project_id') . ' = ' . (int) $projectId);

		return $query;
	}
}
