<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Controller\Comment\Ajax;

use App\Tracker\Model\ActivityModel;
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
		/* @type \JTracker\Application $application */
		$application = $this->getContainer()->get('app');

		$application->getUser()->authorize('create');

		$comment      = $application->input->get('text', '', 'raw');
		$issue_number = $application->input->getInt('issue_number');
		$project      = $application->getProject();

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
			'<hr /><sub>This comment was created with the <a href="%1$s">%2$s Application</a> at <a href="%3$s">%4$s</a>.</sub>',
			'https://github.com/joomla/jissues', 'J!Tracker',
			$application->get('uri')->base->full . 'tracker/' . $project->alias . '/' . $issue_number,
			str_replace(['http://', 'https://'], '', $application->get('uri')->base->full) . $project->alias . '/' . $issue_number
		);

		/* @type \Joomla\Github\Github $github */
		$github = $this->getContainer()->get('gitHub');

		$data = new \stdClass;

		/* @type \Joomla\Database\DatabaseDriver $db */
		$db   = $this->getContainer()->get('db');

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

		(new ActivityModel($db))
			->addActivityEvent(
				'comment', $data->created_at, $data->opened_by, $project->project_id, $issue_number, $data->comment_id, $data->text, $data->text_raw
			);

		$data->activities_id = $db->insertid();

		$this->response->data    = $data;
		$this->response->message = g11n3t('Your comment has been submitted');
	}
}
