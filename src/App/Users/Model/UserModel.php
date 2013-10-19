<?php
/**
 * Part of the Joomla Tracker's Users Application
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Users\Model;

use JTracker\Authentication\GitHub\GitHubUser;
use JTracker\Model\AbstractTrackerDatabaseModel;
use JTracker\Container;

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
	 * @return  GitHubUser
	 *
	 * @since   1.0
	 */
	public function getItem($itemId = null)
	{
		/* @type \JTracker\Application $application */
		$application = Container::retrieve('app');

		if (!$itemId)
		{
			return $application->getUser();
		}

		try
		{
			$user = new GitHubUser($itemId);
		}
		catch (\RuntimeException $e)
		{
			// Load a blank user
			$user = new GitHubUser;
		}

		return $user;
	}
}
