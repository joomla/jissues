<?php
/**
 * Part of the Joomla Tracker Model Package
 *
 * @copyright  Copyright (C) 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Controller\Fetch\Ajax;

use JTracker\Controller\AbstractAjaxController;

/**
 * Controller to respond AJAX request.
 *
 * @since  1.0
 */
class Issues extends AbstractAjaxController
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

		/** @var \Joomla\Database\DatabaseDriver $db */
		$db = $this->getContainer()->get('db');

		$issueNumber = $application->input->getInt('q');

		if ($issueNumber)
		{
			$this->response->data = $db
				->setQuery(
					$db->getQuery(true)
						->select($db->quoteName(['i.issue_number', 'i.title']))
						->from($db->quoteName('#__issues', 'i'))
						->where($db->quoteName('i.project_id') . ' = ' . (int) $application->getProject()->project_id)
						->where($db->quoteName('i.issue_number') . " LIKE '%" . (int) $issueNumber . "%'"),
					0, 10
				)
				->loadAssocList();
		}
	}
}
