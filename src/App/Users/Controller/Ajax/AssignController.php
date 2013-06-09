<?php
/**
 * @package    JTracker\Components\Users
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Users\Controller\Ajax;

use JTracker\Authentication\Database\TableUsers;
use JTracker\Controller\AbstractTrackerController;

/**
 * Default controller class for the Users component.
 *
 * @package  JTracker\Components\Users
 * @since    1.0
 */
class AssignController extends AbstractTrackerController
{
	/**
	 * Execute the controller.
	 *
	 * @since  1.0
	 *
	 * @return  boolean
	 */
	public function execute()
	{
		$response = new \stdClass;

		$response->data  = new \stdClass;
		$response->error = '';
		$response->debug = '';

		ob_start();

		try
		{
			if (false == $this->getApplication()->getUser()->check('manage'))
			{
				throw new \Exception('You are not authorized');
			}

			$input = $this->getInput();
			$db    = $this->getApplication()->getDatabase();

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

				$response->data->message = 'The user has been assigned.';
			}
			else
			{
				if (!$check)
				{
					throw new \Exception('The user is not assigned to this group.');
				}

				$this->unAssign($tableUsers->id, $groupId);

				$response->data->message = 'The user has been unassigned.';
			}
		}
		catch (\Exception $e)
		{
			$response->error = $e->getMessage();
		}

		$response->debug = ob_get_clean();

		header('Content-type: application/json');

		echo json_encode($response);

		exit(0);
	}

	/**
	 * Add a user to a group.
	 *
	 * @param   integer  $userId   The user id.
	 * @param   integer  $groupId  The group id.
	 *
	 * @return AssignController
	 */
	private function assign($userId, $groupId)
	{
		$db = $this->getApplication()->getDatabase();

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
	 * @return AssignController
	 */
	private function unAssign($userId, $groupId)
	{
		$db = $this->getApplication()->getDatabase();

		$db->setQuery(
			$db->getQuery(true)
				->delete($db->quoteName('#__user_accessgroup_map'))
				->where($db->quoteName('user_id') . ' = ' . (int) $userId)
				->where($db->quoteName('group_id') . ' = ' . (int) $groupId)
		)->execute();

		return $this;
	}
}
