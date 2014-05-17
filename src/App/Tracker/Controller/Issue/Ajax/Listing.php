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
		//send response.
		$this->response->data = $this->model->getItems();
	}

} 