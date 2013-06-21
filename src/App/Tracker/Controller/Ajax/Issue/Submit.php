<?php
/**
 * @copyright  Copyright (C) 2013 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Tracker\Controller\Ajax\Issue;

use App\Tracker\Table\ActivitiesTable;
use App\Tracker\Table\IssuesTable;

use Joomla\Date\Date;

use JTracker\Controller\AbstractAjaxController;

/**
 * Add issues controller class.
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
	 * @return void
	 */
	protected function prepareResponse()
	{
		$this->getApplication()->getUser()->authorize('create');

		$title    = $this->getInput()->getString('title');
		$body     = $this->getInput()->get('body', '', 'raw');
		$priority = $this->getInput()->getInt('priority');

		if (!$body)
		{
			throw new \Exception('No body received.');
		}

		$project = $this->getApplication()->getProject();

		$data = new \stdClass;

		if ($project->gh_user && $project->gh_project)
		{
			$gitHubResponse = $this->getApplication()->getGitHub()
				->issues->create(
					$project->gh_user, $project->gh_project,
					$title, $body
				);

			if (!isset($gitHubResponse->id))
			{
				throw new \Exception('Invalid response from GitHub');
			}

			$data->created_at = $gitHubResponse->created_at;
			$data->opened_by  = $gitHubResponse->user->login;
			$data->number     = $gitHubResponse->number;
			$data->text       = $gitHubResponse->body;

			$data->text = $this->getApplication()->getGitHub()->markdown
				->render(
					$body,
					'gfm',
					$project->gh_user . '/' . $project->gh_project
				);
		}
		else
		{
			$date = new Date;

			$data->created_at = $date->format($this->getApplication()->getDatabase()->getDateFormat());
			$data->opened_by  = $this->getApplication()->getUser()->username;
			$data->number     = '???';

			$data->text_raw = $body;

			$data->text = $this->getApplication()->getGitHub()->markdown
				->render($body, 'markdown');
		}

		// Store the issue
		$table = new IssuesTable($this->getApplication()->getDatabase());

		$table->opened_date     = $data->created_at;
		$table->opened_by       = $data->opened_by;
		$table->project_id      = $project->project_id;
		$table->issue_number    = $data->number;
		$table->priority        = $priority;
		$table->title           = $title;
		$table->description     = $data->text;
		$table->description_raw = $body;

		$table->check()->store();

		// Store the activity
		$table = new ActivitiesTable($this->getApplication()->getDatabase());

		$table->event        = 'open';
		$table->created_date = $data->created_at;
		$table->project_id   = $project->project_id;
		$table->issue_number = $data->number;
		$table->user         = $data->opened_by;

		$table->check()->store();

		$this->response->message = 'Your issue report has been submitted';
	}
}
