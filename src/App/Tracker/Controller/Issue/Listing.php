<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Tracker\Controller\Issue;

use App\Tracker\Controller\DefaultController;

/**
 * List controller class for the Tracker component.
 *
 * @since  1.0
 */
class Listing extends DefaultController
{
	/**
	 * Initialize the controller.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	public function initialize()
	{
		parent::initialize();

		/* @type \JTracker\Application $application */
		$application = $this->container->get('app');

		$application->getUser()->authorize('view');

		$state = $this->model->getState();

		$projectId = $application->getProject()->project_id;

		$state->set('filter.project', $projectId);

		$sort = $application->getUserStateFromRequest('project_' . $projectId . '.filter.sort', 'filter-sort', 0, 'uint');

		// $sort = $this->input->get('project_' . $projectId . '.filter.sort', 'filter-sort', 0, 'uint');

		switch ($sort)
		{
			case 1:
				$state->set('list.ordering', 'a.issue_number');
				$state->set('list.direction', 'ASC');
				break;

			case 2:
				$state->set('list.ordering', 'a.modified_date');
				$state->set('list.direction', 'DESC');
				break;

			case 3:
				$state->set('list.ordering', 'a.modified_date');
				$state->set('list.direction', 'ASC');
				break;

			default:
				$state->set('list.ordering', 'a.issue_number');
				$state->set('list.direction', 'DESC');
		}

		$state->set('filter.sort', $sort);

		$state->set('filter.priority',
			$application->getUserStateFromRequest('project_' . $projectId . '.filter.priority', 'filter-priority', 0, 'uint')
		);

		$state->set('filter.status',
			$application->getUserStateFromRequest('project_' . $projectId . '.filter.status', 'filter-status', 1, 'uint')
		);

		$state->set('filter.search',
			$application->getUserStateFromRequest('project_' . $projectId . '.filter.search', 'filter-search', '', 'string')
		);

		$this->model->setState($state);

		return $this;
	}
}
