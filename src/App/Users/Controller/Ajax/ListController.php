<?php
/**
 * @package    JTracker\Components\Users
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Users\Controller\Ajax;

use Joomla\Factory;
use JTracker\Controller\AbstractTrackerController;

/**
 * Default controller class for the Users component.
 *
 * @package  JTracker\Components\Users
 * @since    1.0
 */
class ListController extends AbstractTrackerController
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
		ob_start();

		try
		{
			// TODO: do we need access control here ?
			// @$this->getApplication()->getUser()->authorize('admin');

			$input = $this->getInput();

			$groupId = $input->getInt('group_id');

			$response = new \stdClass;

			$response->data  = new \stdClass;
			$response->error = '';
			$response->message = '';

			if ($groupId)
			{
				$db = $this->getApplication()->getDatabase();

				$query = $db->getQuery(true)
					->select($db->quoteName(array('u.id', 'u.username')))
					->from($db->quoteName('#__users', 'u'));

				$query->leftJoin(
					$db->quoteName('#__user_accessgroup_map', 'm')
					. ' ON ' . $db->quoteName('m.user_id')
					. ' = ' . $db->quoteName('u.id')
				);

				$query->where($db->quoteName('m.group_id') . ' = ' . (int) $groupId);

				$users = $db->setQuery($query, 0, 10)
					->loadAssocList();

				$response->data->options = $users ? : array();
			}
		}
		catch (\Exception $e)
		{
			$response->error = $e->getMessage();
		}

		$errors = ob_get_clean();

		if ($errors)
		{
			$response->error = $errors;
		}

		header('Content-type: application/json');

		echo json_encode($response);

		exit(0);
	}
}
