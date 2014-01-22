<?php
/**
 * Part of the Joomla Tracker's Users Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Users\Model;

use App\Projects\TrackerProject;

use JTracker\Authentication\GitHub\GitHubUser;
use JTracker\Model\AbstractTrackerDatabaseModel;

/**
 * User model class for the Users component.
 *
 * @since  1.0
 */
class UserModel extends AbstractTrackerDatabaseModel
{
	/**
	 * Project object
	 *
	 * @var    TrackerProject
	 * @since  1.0
	 */
	protected $project;

	/**
	 * Get an item.
	 *
	 * @param   integer  $itemId  The item id.
	 *
	 * @return  GitHubUser
	 *
	 * @since   1.0
	 * @throws  \Exception
	 */
	public function getItem($itemId = null)
	{
		if (!$itemId)
		{
			throw new \Exception('No user set');
		}

		try
		{
			$user = new GitHubUser($this->project, $this->db, $itemId);
		}
		catch (\RuntimeException $e)
		{
			// Load a blank user
			$user = new GitHubUser($this->project, $this->db);
		}

		return $user;
	}

	/**
	 * Set the project.
	 *
	 * @param   TrackerProject  $project  The project.
	 *
	 * @return  $this  Method allows chaining
	 *
	 * @since   1.0
	 */
	public function setProject(TrackerProject $project)
	{
		$this->project = $project;

		return $this;
	}
}
