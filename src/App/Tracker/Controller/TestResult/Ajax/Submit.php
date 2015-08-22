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
		$userComment = $application->input->get('comment', '', 'raw');
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

		$gitHubHelper = new GitHubHelper($this->getContainer()->get('gitHub'));

		// Create a comment to submitted on GitHub.
		switch ($result)
		{
			case 0:
				$comment = 'I have not tested this item.';
				break;
			case 1:
				$comment = 'I have tested this item :white_check_mark: successfully' . ' on ' . $sha;
				break;
			case 2:
				$comment = 'I have tested this item :red_circle: unsuccessfully' . ' on ' . $sha;
				break;
			default:
				throw new \UnexpectedValueException('Unexpected test result value.');
				break;
		}

		$comment .= ($userComment) ? '<br />' . $userComment : '';
		$comment .= $gitHubHelper->getApplicationComment($application, $project, $issueNumber);

		$data->comment = $gitHubHelper->addComment($project, $issueNumber, $comment, $user->username, $this->getContainer()->get('db'));

		$this->response->data = json_encode($data);

		$this->response->message = g11n3t('Test successfully added');
	}
}
