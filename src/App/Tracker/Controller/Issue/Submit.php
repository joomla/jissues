<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Tracker\Controller\Issue;

use App\Tracker\Table\ActivitiesTable;
use App\Tracker\Table\IssuesTable;

use Joomla\Date\Date;

use JTracker\Controller\AbstractTrackerController;
use JTracker\Container;

/**
 * Add issues controller class.
 *
 * @since  1.0
 */
class Submit extends AbstractTrackerController
{
	/**
	 * Execute the controller.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 * @throws  \Exception
	 */
	public function execute()
	{
		$application = $this->getApplication();
		$database    = Container::retrieve('db');
		$gitHub      = $application->getGitHub();
		$project     = $application->getProject();

		$application->getUser()->authorize('create');

		$title    = $this->getInput()->getString('title');
		$body     = $this->getInput()->get('body', '', 'raw');
		$priority = $this->getInput()->getInt('priority');

		if (!$body)
		{
			throw new \Exception('No body received.');
		}

		$data = new \stdClass;

		if ($project->gh_user && $project->gh_project)
		{
			$gitHubResponse = $gitHub->issues->create(
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

			$data->text = $gitHub->markdown->render(
					$body,
					'gfm',
					$project->gh_user . '/' . $project->gh_project
				);
		}
		else
		{
			$date = new Date;

			$data->created_at = $date->format($database->getDateFormat());
			$data->opened_by  = $application->getUser()->username;
			$data->number     = '???';

			$data->text_raw = $body;

			$data->text = $gitHub->markdown->render($body, 'markdown');
		}

		// Store the issue
		$table = new IssuesTable($database);

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
		$table = new ActivitiesTable($database);

		$table->event        = 'open';
		$table->created_date = $data->created_at;
		$table->project_id   = $project->project_id;
		$table->issue_number = $data->number;
		$table->user         = $data->opened_by;

		$table->check()->store();

		$application->enqueueMessage('Your issue report has been submitted', 'success');

		$application->redirect(
			$application->get('uri.base.path')
			. 'tracker/' . $project->alias . '/' . $data->number
		);

		return;
	}
}
