<?php
/**
 * Part of the Joomla Tracker's Users Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Users\Model;

use Joomla\Database\DatabaseQuery;
use Joomla\String\String;

use JTracker\Model\AbstractTrackerListModel;

/**
 * Users model class for the Users component.
 *
 * @since  1.0
 */
class UsersModel extends AbstractTrackerListModel
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
		$db    = $this->getDb();
		$query = $db->getQuery(true);

		$query->select(array('id', 'username'));
		$query->from('#__users');

		$filter = $this->state->get('filter.search-user');

		if ($filter)
		{
			// Clean filter variable
			$filter = $db->quote('%' . $db->escape(String::strtolower($filter), true) . '%', false);

			$query->where($db->quoteName('username') . ' LIKE ' . $filter);
		}

		return $query;
	}
}
