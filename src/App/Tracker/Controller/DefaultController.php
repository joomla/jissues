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

		$sort = $application->getUserStateFromRequest('project_' . $projectId . '.filter.sort', 'sort', 'issue', 'word');

		$direction = $application->getUserStateFromRequest('project_' . $projectId . '.filter.direction', 'direction', 'desc', 'word');

		// Filter.sort
		$filter_sort = 0;

		switch (strtolower($sort))
		{
			case 'updated':
				$state->set('list.ordering', 'a.modified_date');
				$filter_sort = $filter_sort +2;
				break;

			default:
				$state->set('list.ordering', 'a.issue_number');
		}

		switch (strtoupper($direction))
		{
			case 'ASC':
				$state->set('list.direction', 'ASC');
				$filter_sort = $filter_sort +1;
				break;

			default:
				$state->set('list.direction', 'DESC');
		}

		$state->set('filter.sort',$filter_sort);
		$state->set('filter.priority',
			$application->getUserStateFromRequest('project_' . $projectId . '.filter.priority', 'priority', 0, 'uint')
		);

		$state->set('filter.state',
			$application->getUserStateFromRequest('project_' . $projectId . '.filter.state', 'state', 0, 'uint')
		);

		$state->set('filter.status',
			$application->getUserStateFromRequest('project_' . $projectId . '.filter.status', 'status', 0, 'uint')
		);

		$state->set('filter.search',
			$application->getUserStateFromRequest('project_' . $projectId . '.filter.search', 'search', '', 'string')
		);

		$state->set('filter.user',
			$application->getUserStateFromRequest('project_' . $projectId . '.filter.user', 'user', 0, 'uint')
		);

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
