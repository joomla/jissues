<?php
/**
 * Part of the Joomla Tracker's Users Application
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Users\Controller\Ajax;

use JTracker\Authentication\Database\TableUsers;
use JTracker\Controller\AbstractAjaxController;
use JTracker\Container;

/**
 * Controller class to assign users to groups
 *
 * @since  1.0
 */
class Assign extends AbstractAjaxController
{
	/**
	 * Prepare the response.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 * @throws  \Exception
	 */
	protected function prepareResponse()
	{
		if (false == $this->getApplication()->getUser()->check('manage'))
		{
			throw new \Exception('You are not authorized');
		}

		$input = $this->getInput();
		$db    = Container::retrieve('db');

		$user    = $input->getCmd('user');
		$groupId = $input->getInt('group_id');
		$assign  = $input->getInt('assign');

		if (!$groupId)
		{
			throw new \Exception('Missing group id');
		}

		$tableUsers = new TableUsers($db);

		$tableUsers->loadByUserName($user);

		if (!$tableUsers->id)
		{
			throw new \Exception('User not found');
		}

		$check = $db->setQuery(
			$db->getQuery(true)
				->from($db->quoteName('#__user_accessgroup_map', 'm'))
				->select('COUNT(*)')
				->where($db->quoteName('group_id') . ' = ' . (int) $groupId)
				->where($db->quoteName('user_id') . ' = ' . (int) $tableUsers->id)
		)->loadResult();

		if ($assign)
		{
			if ($check)
			{
				throw new \Exception('The user is already assigned to this group.');
			}

			$this->assign($tableUsers->id, $groupId);

			$this->response->data->message = g11n3t('The user has been assigned.');
		}
		else
		{
			if (!$check)
			{
				throw new \Exception('The user is not assigned to this group.');
			}

			$this->unAssign($tableUsers->id, $groupId);

			$this->response->data->message = g11n3t('The user has been unassigned.');
		}
	}

	/**
	 * Add a user to a group.
	 *
	 * @param   integer  $userId   The user id.
	 * @param   integer  $groupId  The group id.
	 *
	 * @return  $this  Method allows chaining
	 *
	 * @since   1.0
	 */
	private function assign($userId, $groupId)
	{
		$db = Container::retrieve('db');

		$data = array(
			$db->quoteName('user_id')  => (int) $userId,
			$db->quoteName('group_id') => (int) $groupId
		);

		$db->setQuery(
			$db->getQuery(true)
				->insert($db->quoteName('#__user_accessgroup_map'))
				->columns(array_keys($data))
				->values(implode(',', $data))
		)->execute();

		return $this;
	}

	/**
	 * Remove a user from a group.
	 *
	 * @param   integer  $userId   The user id.
	 * @param   integer  $groupId  The group id.
	 *
	 * @return  $this  Method allows chaining
	 *
	 * @since   1.0
	 */
	private function unAssign($userId, $groupId)
	{
		$db = Container::retrieve('db');

		$db->setQuery(
			$db->getQuery(true)
				->delete($db->quoteName('#__user_accessgroup_map'))
				->where($db->quoteName('user_id') . ' = ' . (int) $userId)
				->where($db->quoteName('group_id') . ' = ' . (int) $groupId)
		)->execute();

		return $this;
	}
}
