<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Controller\TestResult\Ajax;

use App\Tracker\Model\ActivityModel;
use App\Tracker\Model\IssueModel;

use JTracker\Controller\AbstractAjaxController;

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
		/* @type \JTracker\Application $application */
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

		$data   = new \stdClass;
		$result = new \stdClass;

		$result->user  = $application->input->getUsername('user');
		$result->value = $application->input->getUint('result');
		$sha           = $application->input->getCmd('sha');

		$issueModel = new IssueModel($this->getContainer()->get('db'));

		$data->testResults = $issueModel
			->saveTest($issueId, $result->user, $result->value, $sha);

		$event = (new ActivityModel($this->getContainer()->get('db')))
			->addActivityEvent(
				'alter_testresult', 'now', $user->username,
				$project->project_id, $issueModel->getIssueNumberById($issueId), null,
				json_encode($result)
			);

		$data->event = new \stdClass;

		foreach ($event as $k => $v)
		{
			$data->event->$k = $v;
		}

		$data->event->text = json_decode($data->event->text);

		$this->response->data = json_encode($data);

		$this->response->message = g11n3t('Test successfully added');
	}
}
