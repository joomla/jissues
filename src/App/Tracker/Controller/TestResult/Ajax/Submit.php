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

use Joomla\Date\Date;
use JTracker\Controller\AbstractAjaxController;

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
				. ($result == 1 ? ':green_heart: successfully' : ':red_circle: unsuccessfully')
				. '<br />'
				. $comment;

			$data->comment = $this->addComment($project, $issueNumber, $comment);
		}
		else
		{
			$data->comment = null;
		}

		$this->response->data = json_encode($data);

		$this->response->message = g11n3t('Test successfully added');
	}

	/**
	 * Add a comment on GitHub.
	 *
	 * @param $project
	 * @param $issueNumber
	 * @param $comment
	 *
	 * @return \stdClass
	 *
	 * @throws \Exception
	 */
	private function addComment($project, $issueNumber, $comment)
	{
		$data = new \stdClass;

		/* @type \Joomla\Github\Github $gitHub */
		$gitHub = $this->getContainer()->get('gitHub');

		/* @type \Joomla\Database\DatabaseDriver $db */
		$db   = $this->getContainer()->get('db');

		if ($project->gh_user && $project->gh_project)
		{
			$gitHubResponse = $gitHub->issues->comments->create(
				$project->gh_user, $project->gh_project, $issueNumber, $comment
			);

			if (!isset($gitHubResponse->id))
			{
				throw new \Exception('Invalid response from GitHub');
			}

			$data->created_at = $gitHubResponse->created_at;
			$data->opened_by = $gitHubResponse->user->login;
			$data->comment_id = $gitHubResponse->id;
			$data->text_raw = $gitHubResponse->body;

			$data->text = $gitHub->markdown->render(
				$comment,
				'gfm',
				$project->gh_user . '/' . $project->gh_project
			);
		}
		else
		{
			/* @type \JTracker\Application $application */
			$application = $this->getContainer()->get('app');

			$date = new Date;

			$data->created_at = $date->format($db->getDateFormat());
			$data->opened_by  = $application->getUser()->username;
			$data->comment_id = '???';
			$data->text_raw = $comment;
			$data->text = $gitHub->markdown->render($comment, 'markdown');
		}

		(new ActivityModel($db))
			->addActivityEvent(
				'comment', $data->created_at, $data->opened_by, $project->project_id,
				$issueNumber, $data->comment_id, $data->text, $data->text_raw
			);

		$data->activities_id = $db->insertid();

		$date = new Date($data->created_at);
		$data->created_at = $date->format('j M Y');

		return $data;
	}
}
