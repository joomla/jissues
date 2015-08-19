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

		switch ($result)
		{
			case 0:
				$resultText = 'I have not tested this item.';
				break;
			case 1:
				$resultText = 'I have tested this item :white_check_mark: successfully';
				break;
			case 2:
				$resultText = 'I have tested this item :red_circle: unsuccessfully';
				break;
			default:
				throw new \UnexpectedValueException('Unexpected test result value.');
				break;
		}

		// Create a comment to submitted on GitHub.
		$comment = $resultText . ' on ' . $sha
			. '<br />'
			. $userComment;

		$comment .= sprintf(
			'<hr /><sub>This comment was created with the <a href="%1$s">%2$s Application</a> at <a href="%3$s">%4$s</a>.</sub>',
			'https://github.com/joomla/jissues',
			'J!Tracker',
			$application->get('uri')->base->full . 'tracker/' . $project->alias . '/' . $issueNumber,
			str_replace(['http://', 'https://'], '', $application->get('uri')->base->full) . $project->alias . '/' . $issueNumber
		);

		$data->comment = (new GitHubHelper($this->getContainer()->get('gitHub')))
			->addComment($project, $issueNumber, $comment, $user->username, $this->getContainer()->get('db'));

		$this->response->data = json_encode($data);

		$this->response->message = g11n3t('Test successfully added');
	}
}
