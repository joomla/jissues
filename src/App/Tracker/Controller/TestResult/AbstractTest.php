<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Controller\TestResult;

use App\Tracker\Model\ActivityModel;
use App\Tracker\Model\IssueModel;

use JTracker\Controller\AbstractAjaxController;
use JTracker\Github\GithubFactory;

/**
 * Abstract test result controller class.
 *
 * @since  1.0
 */
abstract class AbstractTest extends AbstractAjaxController
{
	/**
	 * Add a human test result.
	 *
	 * @param   string   $eventType  The event type.
	 * @param   integer  $itemId     The item id.
	 * @param   string   $userName   The username.
	 * @param   integer  $result     The test result.
	 *
	 * @since  1.0
	 *
	 * @return  object
	 */
	protected function addTest($eventType, $itemId, $userName, $result)
	{
		/* @type \JTracker\Application $application */
		$application = $this->getContainer()->get('app');
		$user        = $application->getUser();
		$project     = $application->getProject();
		$issueModel = new IssueModel($this->getContainer()->get('db'));

		$data   = new \stdClass;

		$data->testResults = $issueModel
			->saveTest($itemId, $userName, $result);

		$resultData = new \stdClass;

		$resultData->user = $userName;
		$resultData->value = $result;

		$event = (new ActivityModel($this->getContainer()->get('db')))
			->addActivityEvent(
				$eventType, 'now', $user->username,
				$project->project_id,
				$issueModel->getIssueNumberById($itemId),
				null, json_encode($resultData)
			);

		$data->event = new \stdClass;

		foreach ($event as $k => $v)
		{
			$data->event->$k = $v;
		}

		$data->event->text = json_decode($data->event->text);

		return json_encode($data);
	}

	/**
	 * Update a status on GitHub.
	 *
	 * @param   integer  $itemId  The item id.
	 *
	 * @since  1.0
	 *
	 * @return $this
	 */
	protected function updateStatus($itemId)
	{
		/* @type \JTracker\Application $application */
		$application = $this->getContainer()->get('app');
		$project     = $application->getProject();

		if ($project->getGh_Editbot_User() && $project->getGh_Editbot_Pass())
		{
			$issueModel = new IssueModel($this->getContainer()->get('db'));

			$gitHub = GithubFactory::getInstance(
				$this->getContainer()->get('app'), true,
				$project->getGh_Editbot_User(), $project->getGh_Editbot_Pass()
			);

			$pullRequest = $gitHub->pulls->get(
				$project->gh_user, $project->gh_project, $issueModel->getIssueNumberById($itemId)
			);

			$project->runActions(
				'GitHub', 'UpdateStatus',
				['pullRequest' => $pullRequest, 'GitHub' => $gitHub]
			);
		}

		return $this;
	}
}
