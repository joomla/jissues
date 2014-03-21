<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Controller\Issue;

use App\Tracker\Model\IssueModel;
use App\Tracker\Table\ActivitiesTable;

use Joomla\Date\Date;

use JTracker\Controller\AbstractTrackerController;

/**
 * Controller class to save an item via the tracker component.
 *
 * @since  1.0
 */
class Save extends AbstractTrackerController
{
	/**
	 * Execute the controller.
	 *
	 * @return  string  The rendered view.
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	public function execute()
	{
		/* @type \JTracker\Application $application */
		$application = $this->container->get('app');

		$application->getUser()->authorize('edit');

		$src = $application->input->get('item', array(), 'array');

		try
		{
			// Save the record.
			(new IssueModel($this->container->get('db')))
				->save($src);

			$comment = $application->input->get('comment', '', 'raw');

			// Save the comment.
			if ($comment)
			{
				$project        = $application->getProject();
				$issue_number   = $src['issue_number'];

				/* @type \Joomla\Github\Github $github */
				$github = $this->container->get('gitHub');

				$data = new \stdClass;
				$db   = $this->container->get('db');

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
					$data->opened_by  = $application->getUser()->username;
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
			}

			$application->enqueueMessage('The changes have been saved.', 'success')
				->redirect(
				'/tracker/' . $application->input->get('project_alias') . '/' . $src['issue_number']
			);
		}
		catch (\Exception $e)
		{
			$application->enqueueMessage($e->getMessage(), 'error');

			if (!empty($src['id']))
			{
				$application->redirect(
					$application->get('uri.base.path')
					. 'tracker/' . $application->input->get('project_alias') . '/' . $src['id'] . '/edit'
				);
			}
			else
			{
				$application->redirect(
					$application->get('uri.base.path')
					. 'tracker/' . $application->input->get('project_alias')
				);
			}
		}

		parent::execute();
	}
}
