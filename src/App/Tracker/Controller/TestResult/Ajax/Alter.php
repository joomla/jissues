<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Controller\TestResult\Ajax;

use App\Projects\TrackerProject;
use App\Tracker\Model\ActivityModel;
use App\Tracker\Model\IssueModel;

use JTracker\Controller\AbstractAjaxController;
use JTracker\Github\GithubFactory;

/**
 * Alter test result controller class.
 *
 * @since  1.0
 */
class Alter extends AbstractAjaxController
{
	/**
	 * Prepare the response.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 * @throws  \Exception
	 */
	protected function prepareResponse()
	{
		/** @var \JTracker\Application $application */
		$application = $this->getContainer()->get('app');
		$user        = $application->getUser();
		$project     = $application->getProject();

		if (!$user->check('edit'))
		{
			throw new \Exception('You are not allowed to alter this item.');
		}

		$issueId  = $application->input->getUint('issueId');

		if (!$issueId)
		{
			throw new \Exception('No issue ID received.');
		}

		$this->setDispatcher($application->getDispatcher());
		$this->addEventListener('tests');
		$this->setProjectGitHubBot($project);

		$data   = new \stdClass;
		$result = new \stdClass;

		$result->user  = $application->input->getUsername('user');
		$result->value = $application->input->getUint('result');
		$sha           = $application->input->getCmd('sha');

		if (!$sha)
		{
			throw new \Exception('Missing commit SHA.');
		}

		$issueModel = new IssueModel($this->getContainer()->get('db'));

		$data->testResults = $issueModel->saveTest($issueId, $result->user, $result->value, $sha);

		$issueNumber = $issueModel->getIssueNumberById($issueId);

		$event = (new ActivityModel($this->getContainer()->get('db')))
			->addActivityEvent(
				'alter_testresult', 'now', $user->username,
				$project->project_id, $issueNumber, null,
				json_encode($result)
			);

		$this->triggerEvent('onTestAfterSubmit', ['issueNumber' => $issueNumber, 'data' => $data->testResults]);

		$data->event = new \stdClass;

		foreach ($event as $k => $v)
		{
			$data->event->$k = $v;
		}

		$data->event->text = json_decode($data->event->text);

		$this->response->data = json_encode($data);

		$this->response->message = g11n3t('Test successfully added');
	}

	/**
	 * Set the GitHub object with the credentials from the project or,
	 * if not found, with those from the configuration file.
	 *
	 * @param   TrackerProject  $project  The Project object.
	 *
	 * @since   1.0
	 * @return $this
	 */
	protected function setProjectGitHubBot(TrackerProject $project)
	{
		// If there is a bot defined for the project, prefer it over the config credentials.
		if ($project->gh_editbot_user && $project->gh_editbot_pass)
		{
			$this->github = GithubFactory::getInstance(
				$this->getContainer()->get('app'), true, $project->gh_editbot_user, $project->gh_editbot_pass
			);
		}
		else
		{
			$this->github = GithubFactory::getInstance(
				$this->getContainer()->get('app')
			);
		}

		return $this;
	}
}
