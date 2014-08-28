<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Controller;

use App\Tracker\Model\CategoryModel;
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

		$this->model->setProject($application->getProject());
		$this->view->setProject($application->getProject());

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

		$projectId = $application->getProject()->project_id;

		// Set up filters
		$sort       = $application->getUserStateFromRequest('project_' . $projectId . '.filter.sort', 'sort', 'issue', 'word');
		$direction  = $application->getUserStateFromRequest('project_' . $projectId . '.filter.direction', 'direction', 'desc', 'word');

		// Update the sort filters from the GET request
		switch (strtolower($sort))
		{
			case 'updated':
				$state->set('list.ordering', 'a.modified_date');
				$sort = $sort + 2;
				break;

			default:
				$sort = 0;
		}

		switch (strtoupper($direction))
		{
			case 'ASC':
				$state->set('list.direction', 'ASC');
				$sort++;
				break;

			default:
				$state->set('list.direction', 'DESC');
		}

		$state->set('filter.sort', $sort);

		$priority = $application->getUserStateFromRequest('project_' . $projectId . '.filter.priority', 'priority', 0, 'cmd');

		// Update the priority filter from the GET request
		switch (strtolower($priority))
		{
			case 'critical':
				$priority = 1;
				break;

			case 'urgent':
				$priority = 2;
				break;

			case 'medium':
				$priority = 3;
				break;

			case 'low':
				$priority = 4;
				break;

			case 'very-low':
				$priority = 5;
				break;
		}

		$state->set('filter.priority', $priority);

		$issuesState = $application->getUserStateFromRequest('project_' . $projectId . '.filter.state', 'state', 'open', 'word');

		// Update the state filter from the GET request
		switch (strtolower($issuesState))
		{
			case 'closed':
				$issuesState = 1;
				break;
		}

		$state->set('filter.state', $issuesState);

		$state->set('filter.status',
			$application->getUserStateFromRequest('project_' . $projectId . '.filter.status', 'status', 0, 'uint')
		);

		$state->set('filter.search',
			$application->getUserStateFromRequest('project_' . $projectId . '.filter.search', 'search', '', 'string')
		);

		$user = $application->getUserStateFromRequest('project_' . $projectId . '.filter.user', 'user', 0, 'word');

		// Update the user filter from the GET request
		switch ((string) $user)
		{
			case 'created':
				$user = 1;
				break;

			case 'participated':
				$user = 2;
				break;
		}

		$state->set('filter.user', $user);

		$categoryAlias = $application->getUserStateFromRequest('project_' . $projectId . '.filter.categoryAlias', 'category', '', 'cmd');

		// Update the category filter from the GET request
		if ($categoryAlias != '')
		{
			$categoryId = 0;

			$categoryModel = new CategoryModel($this->getContainer()->get('db'));
			$category = $categoryModel->setProject($application->getProject())->getByAlias($categoryAlias);

			if ($category)
			{
				$categoryId = $category->id;
			}
		}
		else
		{
			$categoryId = $application->getUserStateFromRequest('project_' . $projectId . '.filter.category', 'category', 0, 'uint');
		}

		$state->set('filter.category', (int) $categoryId);

		$state->set('stools-active',
			$application->input->get('stools-active', 0, 'uint')
		);

		if ($application->getUser()->username)
		{
			$state->set('username', $application->getUser()->username);
		}

		// Update the page from the GET request
		$state->set('page',
			$application->getUserStateFromRequest('project_' . $projectId . '.page', 'page', 1, 'uint')
		);

		$this->model->setState($state);
	}
}
