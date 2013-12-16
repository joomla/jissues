<?php
/**
 * Part of the Joomla Tracker's Users Application
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Users\Controller\Ajax;

use JTracker\Controller\AbstractAjaxController;

/**
 * Default controller class for the Users component.
 *
 * @since  1.0
 */
class Search extends AbstractAjaxController
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
		$input = $this->container->get('app')->input;

		$search       = $input->get('query');
		$inGroupId    = $input->getInt('in_group_id');
		$notInGroupId = $input->getInt('not_in_group_id');

		if ($search)
		{
			$db = $this->container->get('db');

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

			$this->response->data->options = $users ? : array();
		}
	}
}
