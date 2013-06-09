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
class SearchController extends AbstractTrackerController
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
		// TODO: do we need access control here ?
		// @$this->getApplication()->getUser()->authorize('admin');

		$response = new \stdClass;

		$response->data          = new \stdClass;
		$response->data->message = '';
		$response->data->options = array();
		$response->error         = '';

		try
		{
			$input = $this->getInput();

			$search       = $input->get('query');
			$inGroupId    = $input->getInt('in_group_id');
			$notInGroupId = $input->getInt('not_in_group_id');

			if ($search)
			{
				$db = $this->getApplication()->getDatabase();

				$query = $db->getQuery(true)
					->select('DISTINCT ' . $db->quoteName('u.username'))
					->from($db->quoteName('#__users', 'u'))
					->where($db->quoteName('u.username') . ' LIKE ' . $db->quote('%' . $db->escape($search) . '%'));

				if ($inGroupId || $notInGroupId)
				{
					$query->leftJoin(
						$db->quoteName('#__user_accessgroup_map', 'm')
						. ' ON ' . $db->quoteName('m.user_id')
						. ' = ' . $db->quoteName('u.id')
					);

					if ($inGroupId)
					{
						$query->where($db->quoteName('m.group_id') . ' = ' . (int) $inGroupId);
					}
					elseif ($notInGroupId)
					{
						$query->where(
							$db->quoteName('u.id') . ' NOT IN ('
							. $db->getQuery(true)
								->from($db->quoteName('#__user_accessgroup_map'))
								->select($db->quoteName('user_id'))
								->where($db->quoteName('group_id') . ' = ' . (int) $notInGroupId)
							. ')'
						);
					}
				}

				$users = $db->setQuery($query, 0, 10)
					->loadColumn();

				$response->data->options = $users ? : array();
			}
		}
		catch (\Exception $exception)
		{
			$response->error = $exception->getMessage();
		}

		header('Content-type: application/json');

		echo json_encode($response);

		exit(0);
	}
}
