<?php
/**
 * Part of the Joomla Tracker Model Package
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Controller\Issue\Ajax;

use JTracker\Controller\AbstractAjaxController;
use JTracker\View\Renderer;
use JTracker\Pagination\TrackerPagination;

use App\Tracker\Model\IssuesModel;

use Joomla\Uri\Uri;

/**
 * Listing controller to respond ajax request.
 *
 * @package  App\Tracker\Controller\Issue\Ajax
 *
 * @since    1.0
 */
class Listing extends AbstractAjaxController
{
	/**
	 * Setting model state that will be used for filtering.
	 *
	 * @param   \App\Tracker\Model\IssuesModel  $model  The issues model
	 *
	 * @return  \Joomla\Registry\Registry
	 *
	 * @since 1.0
	 */
	private function setModelState(IssuesModel $model)
	{
		// Get the state object
		$state = $model->getState();

		// Pagination
		$application = $this->getContainer()->get('app');
		$limit = $application->getUserStateFromRequest('list.limit', 'limit', 20, 'int');
		$page  = $application->input->getInt('page');

		$value      = $page ? ($page - 1) * $limit : 0;
		$limitStart = ($limit != 0 ? (floor($value / $limit) * $limit) : 0);

		$state->set('list.start', $limitStart);
		$state->set('list.limit', $limit);

		// Get project id;

		$projectId = $application->getProject()->project_id;

		// Set filter of project
		$state->set('filter.project', $projectId);

		// Get sort and direction

		$sort = $application->getUserStateFromRequest('project_' . $projectId . '.filter.sort', 'sort', 'issue_number', 'word');

		$direction = $application->getUserStateFromRequest('project_' . $projectId . '.filter.direction', 'direction', 'desc', 'word');

		switch (strtolower($sort))
		{
			case 'modified_date':
				$state->set('list.ordering', 'a.modified_date');
				break;

			default:
				$state->set('list.ordering', 'a.issue_number');
		}

		switch (strtoupper($direction))
		{
			case 'ASC':
				$state->set('list.direction', 'ASC');
				break;

			default:
				$state->set('list.direction', 'DESC');
		}

		$state->set('filter.sort', $sort);

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

		$model->setState($state);
	}

	/**
	 * Prepare the response.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	protected function prepareResponse()
	{
		// Load the application
		$application = $this->getContainer()->get('app');

		// Load the model
		$model = new IssuesModel($this->getContainer()->get('db'), $application->input);

		// Get allowed user for view;
		$application->getUser()->authorize('view');

		// Set Current project;
		$model->setProject($application->getProject(true));

		// Set model state
		$this->setModelState($model);

		// Pagination
		$paginationObject = new TrackerPagination(new Uri($this->getContainer()->get('app')->get('uri.request')));
		$model->setPagination($paginationObject);

		// Get list items
		$listItems = $model->getAjaxItems();

		// Get total pages
		$pagesTotal = $model->getPagination()->getPagesTotal();

		// Render the label html for each item
		$renderer = new Renderer\TrackerExtension($this->getContainer());

		foreach ($listItems as $label)
		{
			$label->labelHtml = $renderer->renderLabels($label->labels);
		}

		// Prepare the response.
		$items                = array('items' => $listItems, 'pagesTotal' => $pagesTotal);
		$this->response->data = $items;
	}
}
