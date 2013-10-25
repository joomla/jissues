<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Tracker\Controller\Comment\Ajax;

use App\Tracker\Table\ActivitiesTable;
use Joomla\Date\Date;
use Joomla\Registry\Registry;

use JTracker\Controller\AbstractAjaxController;
use JTracker\Container;

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
	 * @return  mixed
	 *
	 * @since   1.0
	 * @throws  \Exception
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

		/* @type \Joomla\Github\Github $github */
		$github = Container::retrieve('gitHub');

		$data = new \stdClass;
		$db   = Container::retrieve('db');

		if ($project->gh_user && $project->gh_project)
		{
			$gitHubResponse = $github->issues->comments->create(
				$project->gh_user, $project->gh_project, $issue_number, $comment
			);

			if (!isset($gitHubResponse->id))
			{
				throw new \Exception('Invalid response from GitHub');
			}

			$data->created_at = $gitHubResponse->created_at;
			$data->opened_by  = $gitHubResponse->user->login;
			$data->comment_id = $gitHubResponse->id;
			$data->text_raw   = $gitHubResponse->body;

			$data->text = $github->markdown->render(
				$comment,
				'gfm',
				$project->gh_user . '/' . $project->gh_project
			);
		}
		else
		{
			$date = new Date;

			$data->created_at = $date->format($db->getDateFormat());
			$data->opened_by  = $this->getApplication()->getUser()->username;
			$data->comment_id = '???';

			$data->text_raw = $comment;

			$data->text = $github->markdown->render($comment, 'markdown');
		}

		$table = new ActivitiesTable($db);

		$table->event         = 'comment';
		$table->created_date  = $data->created_at;
		$table->project_id    = $project->project_id;
		$table->issue_number  = $issue_number;
		$table->gh_comment_id = $data->comment_id;
		$table->user          = $data->opened_by;
		$table->text          = $data->text;
		$table->text_raw      = $data->text_raw;

		$table->store();

		$data->activities_id = $table->activities_id;

		$this->response->data    = $data;
		$this->response->message = 'Your comment has been submitted';
	}
}
