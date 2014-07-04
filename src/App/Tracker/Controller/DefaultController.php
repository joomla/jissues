<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Controller;

use App\Tracker\View\Issues\IssuesHtmlView;

use JTracker\Controller\AbstractTrackerListController;

/**
 * Default controller class for the Tracker component.
 *
 * @since  1.0
 */
class DefaultController extends AbstractTrackerListController
{
	/**
	 * View object
	 *
	 * @var    IssuesHtmlView
	 * @since  1.0
	 */
	protected $view;

	/**
	 * The default view for the app
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $defaultView = 'issues';

	/**
	 * Initialize the controller.
	 *
	 * @return  $this  Method supports chaining
	 *
	 * @since   1.0
	 */
	public function initialize()
	{
		parent::initialize();

		/* @type \JTracker\Application $application */
		$application = $this->getContainer()->get('app');

		$application->getUser()->authorize('view');

		$this->model->setProject($this->getContainer()->get('app')->getProject());
		$this->view->setProject($this->getContainer()->get('app')->getProject());

		$this->setModelState();

		return $this;
	}

	/**
	 * Setting model state that will be used for filtering.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	private function setModelState()
	{
		/* @type \JTracker\Application $application */
		$application = $this->getContainer()->get('app');

		$state = $this->model->getState();

		// Get project id;
		$projectId = $application->getProject()->project_id;

		// Set filter of project
		$state->set('filter.project', $projectId);

		$sort = $application->getUserStateFromRequest('project_' . $projectId . '.get.filter.sort', 'sort', 'issue', 'word');

		$direction = $application->getUserStateFromRequest('project_' . $projectId . 'get.filter.direction', 'direction', 'desc', 'word');

		// Filter.sort for get queries
		$filter_sort = 0;

		switch (strtolower($sort))
		{
			case 'updated':
				$state->set('list.ordering', 'a.modified_date');
				$filter_sort = $filter_sort + 2;
				break;

			default:
				$state->set('list.ordering', 'a.issue_number');
		}

		switch (strtoupper($direction))
		{
			case 'ASC':
				$state->set('list.direction', 'ASC');
				$filter_sort++;
				break;

			default:
				$state->set('list.direction', 'DESC');
		}

		$state->set('filter.sort', $filter_sort);

		// Filter.priority for get queries
		$priority = $application->getUserStateFromRequest('project_' . $projectId . 'get.filter.priority', 'priority', 0, 'word');

		$filter_priority = 0;

		switch (strtolower($priority))
		{
			case 'critical':
				$filter_priority = 1;
				break;

			case 'urgent':
				$filter_priority = 2;
				break;

			case 'medium':
				$filter_priority = 3;
				break;

			case 'low':
				$filter_priority = 4;
				break;

			case 'very-low':
				$filter_priority = 5;
				break;
		}

		$state->set('filter.priority', $filter_priority);

		// Filter.state for get queries
		$issue_state = $application->getUserStateFromRequest('project_' . $projectId . 'get.filter.state', 'state', 'open', 'word');

		$filter_state = 0;

		switch (strtolower($issue_state))
		{
			case 'closed':
				$filter_state = 1;
				break;
		}

		$state->set('filter.state', $filter_state);

		// Filter.status for get queries
		$status = $application->getUserStateFromRequest('project_' . $projectId . '.filter.status', 'status', '', 'word');

		$state->set('filter.status', $this->model->getStatusByName($status));

		// Filter.search for word

		$state->set('filter.search',
			$application->getUserStateFromRequest('project_' . $projectId . '.filter.search', 'search', '', 'string')
		);

		$user = $application->getUserStateFromRequest('project_' . $projectId . '.filter.user', 'user', 0, 'word');

		$filter_user = 0;

		switch ($user)
		{
			case 'created':
				$filter_user = 1;
				break;

			case 'participated':
				$filter_user = 2;
				break;
		}

		// Filter.user for get queries
		$state->set('filter.user', $filter_user);

		$state->set('stools-active',
			$application->input->get('stools-active', 0, 'uint')
		);

		if ($application->getUser()->username)
		{
			$state->set('username', $application->getUser()->username);
		}

		$this->model->setState($state);
	}
}
