<?php
/**
 * Part of the Joomla Tracker's Groups Application
 *
 * @copyright  Copyright (C) 2012-2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Groups\Model;

use App\Groups\Table\GroupsTable;

use Joomla\Database\DatabaseQuery;

use JTracker\Model\AbstractTrackerListModel;

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
		$projectId = $this->getProject()->project_id;

		$db    = $this->getDb();
		$query = $db->getQuery(true)
			->select('a.*')
			->from($db->quoteName((new GroupsTable($db))->getTableName(), 'a'))
			->where($db->quoteName('project_id') . ' = ' . (int) $projectId);

		return $query;
	}
}
