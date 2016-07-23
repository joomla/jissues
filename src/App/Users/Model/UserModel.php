<?php
/**
 * Part of the Joomla Tracker's Users Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Users\Model;

use Joomla\Filter\InputFilter;

use JTracker\Authentication\GitHub\GitHubUser;
use JTracker\Authentication\Database\TableUsers;
use JTracker\Model\AbstractTrackerDatabaseModel;

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
	 * @throws  \Exception
	 */
	public function getItem($itemId = null)
	{
		try
		{
			$user = new GitHubUser($this->getProject(), $this->db, $itemId);
		}
		catch (\RuntimeException $e)
		{
			// Load a blank user
			$user = new GitHubUser($this->getProject(), $this->db);
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

		$data = [];

		$data['id'] = $filter->clean($src['id'], 'int');

		if (!$data['id'])
		{
			throw new \UnexpectedValueException('Missing ID');
		}

		$data['params'] = json_encode($src['params']);

		(new TableUsers($this->db))->save($data);

		return $this;
	}
}
