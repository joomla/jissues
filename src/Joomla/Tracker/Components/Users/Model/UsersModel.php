<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tracker\Components\Users\Model;

use Joomla\Database\DatabaseQuery;
use Joomla\Factory;
use Joomla\Tracker\Model\AbstractTrackerListModel;

/**
 * Default model class for the Tracker component.
 *
 * @package  JTracker\Components\Tracker
 * @since    1.0
 */
class UsersModel extends AbstractTrackerListModel
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
		Factory::$application->mark('Fetch users list');

		return $this->db->getQuery(true)
			->select(array('id', 'username'))
			->from('#__users');
	}
}
