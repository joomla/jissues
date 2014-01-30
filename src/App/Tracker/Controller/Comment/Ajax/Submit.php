<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Controller\Comment\Ajax;

use App\Tracker\Table\ActivitiesTable;
use Joomla\Date\Date;

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
	 * @return  mixed
	 *
	 * @since   1.0
	 * @throws  \Exception
	 */
	protected function prepareResponse()
	{
		$this->container->get('app')->getUser()->authorize('create');

		$comment      = $this->container->get('app')->input->get('text', '', 'raw');
		$issue_number = $this->container->get('app')->input->getInt('issue_number');

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

		$project = $this->container->get('app')->getProject();

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
			$data->opened_by  = $this->container->get('app')->getUser()->username;
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

		// Update issue
		$db->setQuery(
			$db->getQuery(true)
				->update($db->quoteName('#__issues'))
				->set(
					array(
						$db->quoteName('modified_date') . ' = ' . $db->quote($table->created_date),
						$db->quoteName('modified_by') . ' = ' . $db->quote($table->user)
					)
				)
				->where($db->quoteName('issue_number') . ' = ' . (int) $table->issue_number)
				->where($db->quoteName('project_id') . ' = ' . (int) $table->project_id)
		)->execute();

		$data->activities_id = $table->activities_id;

		$this->response->data    = $data;
		$this->response->message = 'Your comment has been submitted';
	}
}
