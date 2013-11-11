<?php
/**
 * Part of the Joomla Tracker's Users Application
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
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
	 * @var  TrackerProject
	 */
	protected $project;

	/**
	 * Get an item.
	 *
	 * @param   integer  $itemId  The item id.
	 *
	 * @throws \Exception
	 * @return  GitHubUser
	 *
	 * @since   1.0
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
	 * @return $this
	 *
	 * @since   1.0
	 */
	public function setProject(TrackerProject $project)
	{
		$this->project = $project;

		return $this;
	}
}
