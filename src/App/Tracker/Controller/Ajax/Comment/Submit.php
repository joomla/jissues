<?php
/**
 * @copyright  Copyright (C) 2013 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Tracker\Controller\Ajax\Comment;

use App\Tracker\Table\ActivitiesTable;
use Joomla\Date\Date;
use Joomla\Registry\Registry;

use JTracker\Controller\AbstractAjaxController;

/**
 * Add comments controller class.
 *
 * @since  1.0
 */
class Submit extends AbstractAjaxController
{
	/**
	 * Prepare the response.
	 *
	 * @since  1.0
	 * @throws \Exception
	 * @return mixed
	 */
	protected function prepareResponse()
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

		$data = new \stdClass;

		if ($project->gh_user && $project->gh_project)
		{
			$gitHubResponse = $this->getApplication()->getGitHub()
				->issues->comments->create(
					$project->gh_user, $project->gh_project,
					$issue_number, $comment
				);

			if (!isset($gitHubResponse->id))
			{
				throw new \Exception('Invalid response from GitHub');
			}

			$data->created_at = $gitHubResponse->created_at;
			$data->opened_by  = $gitHubResponse->user->login;
			$data->comment_id = $gitHubResponse->number;
			$data->text_raw   = $gitHubResponse->body;

			$data->text = $this->getApplication()->getGitHub()->markdown
				->render(
					$comment,
					'gfm',
					$project->gh_user . '/' . $project->gh_project
				);
		}
		else
		{
			$date = new Date;

			$data->created_at = $date->format($this->getApplication()->getDatabase()->getDateFormat());
			$data->opened_by  = $this->getApplication()->getUser()->username;
			$data->comment_id = '???';

			$data->text_raw = $comment;

			$data->text = $this->getApplication()->getGitHub()->markdown
				->render($comment, 'markdown');
		}

		$table = new ActivitiesTable($this->getApplication()->getDatabase());

		$table->event         = 'comment';
		$table->created_date  = $data->created_at;
		$table->project_id    = $project->project_id;
		$table->issue_number  = $issue_number;
		$table->gh_comment_id = $data->comment_id;
		$table->user          = $data->opened_by;
		$table->text          = $data->text;
		$table->text_raw      = $data->text_raw;

		$table->store();

		$this->response->message = 'Your comment has been submitted';
	}
}
