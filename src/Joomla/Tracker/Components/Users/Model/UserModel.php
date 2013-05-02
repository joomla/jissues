<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tracker\Components\Users\Model;

use Joomla\Tracker\Authentication\Database\TableUsers;
use Joomla\Tracker\Model\AbstractTrackerDatabaseModel;

/**
 * Model class for the Tracker component.
 *
 * @since  1.0
 */
class UserModel extends AbstractTrackerDatabaseModel
{
	/**
	 * Get an item.
	 *
	 * @param   integer  $id  The item id.
	 *
	 * @return TableUsers
	 */
	public function getItem($id)
	{
		// $table = $this->getTable();
		$table = new TableUsers($this->db);

		$table->load($id);

		return $table;
	}
}
