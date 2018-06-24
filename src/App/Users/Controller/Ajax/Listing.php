<?php
/**
 * Part of the Joomla Tracker's Users Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Users\Controller\Ajax;

use JTracker\Controller\AbstractAjaxController;

/**
 * Default controller class for the Users component.
 *
 * @since  1.0
 */
class Listing extends AbstractAjaxController
{
	/**
	 * Prepare the response.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	protected function prepareResponse()
	{
		/** @var \JTracker\Application $application */
		$application = $this->getContainer()->get('app');

		$application->getUser()->authorize('manage');

		$groupId = $application->input->getInt('group_id');

		$users = [];

		if ($groupId)
		{
			$db = $this->getContainer()->get('db');

			$query = $db->getQuery(true)
				->select($db->quoteName(['u.id', 'u.username']))
				->from($db->quoteName('#__users', 'u'))
				->where($db->quoteName('m.group_id') . ' = ' . (int) $groupId)
				->leftJoin(
					$db->quoteName('#__user_accessgroup_map', 'm')
					. ' ON ' . $db->quoteName('m.user_id')
					. ' = ' . $db->quoteName('u.id')
				);

			$users = $db->setQuery($query)
				->loadAssocList();
		}

		$this->response->data->options = $users ? : [];
	}
}
