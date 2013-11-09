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

use JTracker\Model\AbstractTrackerDatabaseModel;

/**
 * Model to get data for the group edit view
 *
 * @since  1.0
 */
class GroupModel extends AbstractTrackerDatabaseModel
{
	/**
	 * @var integer
	 */
	protected $group_id = 0;

	/**
	 * Method to get a DatabaseQuery object for retrieving the data set from a database.
	 *
	 * @return  DatabaseQuery  A DatabaseQuery object to retrieve the data set.
	 *
	 * @since   1.0
	 */
	public function getItem()
	{
		$table = new GroupsTable($this->getDb());

		//$app = $this->container->get('app');
		$groupId = $this->getGroupId();
		//$app->input->getInt('group_id');

		return $groupId ? $table->load($groupId)->getIterator() : $table->getIterator();
	}

	/**
	 * @return int
	 */
	public function getGroupId()
	{
		if (0 == $this->group_id)
		{
			throw new \UnexpectedValueException('group_id not set');
		}

		return $this->group_id;
	}

	/**
	 * @param int $group_id
	 */
	public function setGroupId($group_id)
	{
		$this->group_id = $group_id;
	}
}
