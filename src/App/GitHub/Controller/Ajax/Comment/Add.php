<?php
/**
 * @copyright  Copyright (C) 2013 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\GitHub\Controller\Ajax\Comment;

use App\Tracker\Table\ActivitiesTable;
use Joomla\Registry\Registry;

use JTracker\Controller\AbstractTrackerController;
use Whoops\Example\Exception;

/**
 * Add comments controller class.
 *
 * @package  JTracker\Components\Users
 * @since    1.0
 */
class Add extends AbstractTrackerController
{
	/**
	 * Execute the controller.
	 *
	 * @since  1.0
	 *
	 * @return  void
	 */
	public function execute()
	{
		$response = new \stdClass;

		$response->data  = new \stdClass;
		$response->error = '';
		$response->message = '';

		ob_start();

		try
		{
			$this->getApplication()->getUser()->authorize('create');

			$comment      = $this->getInput()->get('text', '', 'raw');
			$issue_number = $this->getInput()->getInt('issue_number');

			if (!$issue_number)
			{
				throw new \Exception('No issue number received.');
			}

			if (!$comment)
			{
				throw new \Exception('You should write a comment first...');
			}

			// @todo removeMe :(
			$comment .= sprintf(
				'<br />*You may blame the <a href="%1$s">%2$s Application</a> for transmitting this comment.*',
				'https://github.com/joomla/jissues', 'J!Tracker'
			);

			$project = $this->getApplication()->getProject();

			$gitHubResponse = $this->getApplication()->getGitHub()
				->issues->comments->create(
				$project->gh_user, $project->gh_project,
				$issue_number, $comment
			);

			if (!isset($gitHubResponse->id))
			{
				throw new Exception('Invalid response from GitHub');
			}

			$table = new ActivitiesTable($this->getApplication()->getDatabase());

			$table->created_date = $gitHubResponse->created_at;
			$table->event = 'comment';
			$table->gh_comment_id = $gitHubResponse->id;
			$table->issue_number = $issue_number;
			$table->project_id = $project->project_id;
			$table->text = $gitHubResponse->body;
			$table->text_raw = $comment;
			$table->user = $gitHubResponse->user->login;

			$table->store();

			// Finally...
			$response->message = 'Your comment has been submitted';
		}
		catch (\Exception $e)
		{
			$response->error = $e->getMessage();
		}

		$errors = ob_get_clean();

		if ($errors)
		{
			$response->error .= $errors;
		}

		header('Content-type: application/json');

		echo json_encode($response);

		exit(0);
	}
}
