<?php
/**
 * Part of the Joomla Tracker's Users Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Users\Model;

use App\Projects\TrackerProject;

use Joomla\Filter\InputFilter;

use JTracker\Authentication\GitHub\GitHubUser;
use JTracker\Authentication\Database\TableUsers;
use JTracker\Model\AbstractTrackerDoctrineModel;

/**
 * User model class for the Users component.
 *
 * @since  1.0
 */
class UserModel extends AbstractTrackerDoctrineModel
{
	/**
	 * Project object
	 *
	 * @var    TrackerProject
	 * @since  1.0
	 */
	protected $project;

	/**
	 * The name of the entity.
	 *
	 * @var string
	 *
	 * @since  1.0
	 */
	protected $entityName = 'User';

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
	public function xgetItem($itemId = null)
	{
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
	 * Save the item.
	 *
	 * @param   array  $src  The source.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 * @throws  \UnexpectedValueException
	 */
	public function save(array $src)
	{
		$filter = new InputFilter;

		$data = array();

		$data['id'] = $filter->clean($src['id'], 'int');

		if (!$data['id'])
		{
			throw new \UnexpectedValueException('Missing ID');
		}

		$data['params'] = json_encode($src['params']);

		(new TableUsers($this->db))->save($data);

		return $this;
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
