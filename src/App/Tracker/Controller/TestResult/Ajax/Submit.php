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
use JTracker\Helper\GitHubHelper;

/**
 * Add test result controller class.
 *
 * @since  1.0
 */
class Submit extends AbstractAjaxController
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

		if (!$user->id)
		{
			throw new \Exception('You are not allowed to test this item.');
		}

		$issueId = $application->input->getUint('issueId');
		$result  = $application->input->getUint('result');
		$comment = $application->input->get('comment', '', 'raw');
		$sha     = $application->input->getCmd('sha');

		if (!$issueId)
		{
			throw new \Exception('No issue ID received.');
		}

		$issueModel = new IssueModel($this->getContainer()->get('db'));

		$issueNumber = $issueModel->getIssueNumberById($issueId);

		$data = new \stdClass;

		$data->testResults = $issueModel->saveTest($issueId, $user->username, $result, $sha);

		$event = (new ActivityModel($this->getContainer()->get('db')))
			->addActivityEvent(
				'test_item', 'now', $user->username,
				$project->project_id, $issueNumber, null,
				json_encode($result)
			);

		$data->event = new \stdClass;

		foreach ($event as $k => $v)
		{
			$data->event->$k = $v;
		}

		$data->event->text = json_decode($data->event->text);

		// Check if a comment was submitted
		if ($comment)
		{
			$comment = 'I have tested this item '
				. ($result == 1 ? ':white_check_mark: successfully' : ':red_circle: unsuccessfully')
				. '<br />'
				. $comment;

			$data->comment = (new GitHubHelper($this->getContainer()->get('gitHub')))
				->addComment($project, $issueNumber, $comment, $user->username, $this->getContainer()->get('db'));
		}
		else
		{
			$data->comment = null;
		}

		$this->response->data = json_encode($data);

		$this->response->message = g11n3t('Test successfully added');
	}
}
