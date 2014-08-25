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
		/* @type \JTracker\Application $application */
		$application = $this->getContainer()->get('app');

		$state = $model->getState();

		$projectId = $application->getProject()->project_id;

		// Set up pagination values
		$limit = $application->getUserStateFromRequest('list.limit', 'limit', 20, 'int');
		$page = $application->getUserStateFromRequest('project_' . $projectId . '.page', 'page', 1, 'uint');

		$projectIdFromState = $application->getUserState('projectId', 0);

		// Reset page on project change
		if ($projectId != $projectIdFromState)
		{
			$application->setUserState('projectId', $projectId);
			$page = 1;
		}

		$value      = $page ? ($page - 1) * $limit : 0;
		$limitStart = ($limit != 0 ? (floor($value / $limit) * $limit) : 0);

		$state->set('list.start', $limitStart);
		$state->set('list.limit', $limit);

		// Get sort and direction
		$sort = $application->getUserStateFromRequest('project_' . $projectId . '.filter.sort', 'sort', 0, 'uint');

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

		$state->set('filter.category',
			$application->getUserStateFromRequest('project_' . $projectId . '.filter.category', 'category', 0, 'uint')
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
		$model->setPagination(
			new TrackerPagination(
				new Uri($application->get('uri.request'))
			)
		);

		// Get list items
		$listItems = $model->getAjaxItems();

		// Get total pages
		$pagesTotal  = $model->getPagination()->getPagesTotal();
		$currentPage = $model->getPagination()->getPageNo();

		// Render the label html for each item
		$renderer = new Renderer\TrackerExtension($this->getContainer());

		foreach ($listItems as $item)
		{
			$item->labelHtml     = $renderer->renderLabels($item->labels);
			$item->opened_date   = date('Y-m-d', strtotime($item->opened_date));
			$item->modified_date = date('Y-m-d', strtotime($item->modified_date));
			$item->closed_date   = date('Y-m-d', strtotime($item->closed_date));
		}

		// Prepare the response.
		$items                = array('items' => $listItems, 'pagesTotal' => $pagesTotal, 'currentPage' => $currentPage);
		$this->response->data = (object) $items;
	}
}
