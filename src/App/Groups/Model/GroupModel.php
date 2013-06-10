<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Groups\Model;

use App\Groups\Table\GroupsTable;

use Joomla\Database\DatabaseQuery;
use Joomla\Factory;

use JTracker\Model\AbstractTrackerDatabaseModel;

/**
 * Model to get data for the projects list view
 *
 * @package  JTracker\Components\Tracker
 * @since    1.0
 */
class GroupModel extends AbstractTrackerDatabaseModel
{
	/**
	 * Method to get a DatabaseQuery object for retrieving the data set from a database.
	 *
	 * @return  DatabaseQuery   A DatabaseQuery object to retrieve the data set.
	 *
	 * @since   1.0
	 */
	public function getItem()
	{
		$table = new GroupsTable($this->getDb());

		$groupId = Factory::$application->input->getInt('group_id');

		return $groupId ? $table->load($groupId)->getIterator() : $table->getIterator();
	}
}
