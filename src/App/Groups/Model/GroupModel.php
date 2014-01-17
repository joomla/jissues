<?php
/**
 * Part of the Joomla Tracker's Groups Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
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
	 * The group ID
	 *
	 * @var    integer
	 * @since  1.0
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

		$groupId = $this->getGroupId();

		return $groupId ? $table->load($groupId)->getIterator() : $table->getIterator();
	}

	/**
	 * Get the group id.
	 *
	 * @return  integer
	 *
	 * @since   1.0
	 */
	public function getGroupId()
	{
		if (0 == $this->group_id)
		{
			// A new item.
		}

		return $this->group_id;
	}

	/**
	 * Set the group id.
	 *
	 * @param   integer  $group_id  The group id.
	 *
	 * @return  $this  Method allows chaining
	 *
	 * @since   1.0
	 */
	public function setGroupId($group_id)
	{
		$this->group_id = $group_id;

		return $this;
	}
}
