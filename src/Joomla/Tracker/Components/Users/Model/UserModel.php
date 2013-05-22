<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tracker\Components\Users\Model;

use Joomla\Factory;
use Joomla\Tracker\Authentication\Database\TableUsers;
use Joomla\Tracker\Authentication\GitHub\GitHubUser;
use Joomla\Tracker\Model\AbstractTrackerDatabaseModel;

/**
 * User model class for the Users component.
 *
 * @since  1.0
 */
class UserModel extends AbstractTrackerDatabaseModel
{
	/**
	 * Get an item.
	 *
	 * @param   integer  $itemId  The item id.
	 *
	 * @return  TableUsers
	 *
	 * @since   1.0
	 */
	public function getItem($itemId = null)
	{
		if ($itemId)
		{
			try
			{
				$user = new GitHubUser($itemId);
			}
			catch (\RuntimeException $e)
			{
				echo $e->getMessage();

				// Factory::$application->enqueueMessage($e->getMessage(), 'error');

				// Load a blank user
				$user = new GitHubUser;
			}
		}
		else
		{
			$user = Factory::$application->getUser();
		}

		return $user;
	}
}
