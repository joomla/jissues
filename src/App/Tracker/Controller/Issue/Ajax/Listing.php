<?php
/**
 * Created by PhpStorm.
 * User: allenzhao
 * Date: 5/16/14
 * Time: 7:29 PM
 */

namespace App\Tracker\Controller\Issue\Ajax;


use JTracker\Controller\AbstractAjaxController;

use App\Tracker\Model\IssuesModel;

class Listing extends AbstractAjaxController
{
	/**
	 * Prepare the response.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */

	protected $model;

	protected function prepareResponse()
	{
		//Load the application
		$application = $this->getContainer()->get('app');
		//Load the model
		$this->model = new IssuesModel($this->getContainer()->get('db'), $application->input);
		//get allowed user for view;
		$application->getUser()->authorize('view');
		//set Current project;
		$this->model->setProject($application->getProject(true));
		//get state object
		$state = $this->model->getState();
		//get project id;

		$projectId = $application->getProject()->project_id;
		//set filter of project
		$state->set('filter.project', $projectId);
		//set state
		$this->model->setState($state);

		$sort = $application->getUserStateFromRequest('project_' . $projectId . '.filter.sort', 'filter-sort', 0, 'uint');

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
			$application->getUserStateFromRequest('project_' . $projectId . '.filter.status', 'filter-status', 0, 'uint')
		);

		$state->set('filter.stage',
			$application->getUserStateFromRequest('project_' . $projectId . '.filter.stage', 'filter-stage', 0, 'uint')
		);

		$state->set('filter.search',
			$application->getUserStateFromRequest('project_' . $projectId . '.filter.search', 'filter-search', '', 'string')
		);

		$state->set('filter.user',
			$application->getUserStateFromRequest('project_' . $projectId . '.filter.user', 'filter-user', 0, 'uint')
		);

		$state->set('stools-active',
			$application->input->get('stools-active', 0, 'uint')
		);

		if ($application->getUser()->username)
		{
			$state->set('username', $application->getUser()->username);
		}
		//send response.
		$listItems=$this->model->getItems();

		$this->response->data = $listItems;
	}

} 