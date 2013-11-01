<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Tracker\Controller\Issue;

use App\Tracker\Model\IssueModel;

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

		$application->getUser()->authorize('create');

		$database    = Container::retrieve('db');
		$gitHub      = Container::retrieve('gitHub');
		$project     = $application->getProject();

		// Prepare issue for the store
		$data = array();

		$data['title']   = $this->getInput()->getString('title');
		$body            = $this->getInput()->get('body', '', 'raw');

		if (!$body)
		{
			throw new \Exception('No body received.');
		}

		if ($project->gh_user && $project->gh_project)
		{
			$gitHubResponse = $gitHub->issues->create(
					$project->gh_user, $project->gh_project,
					$data['title'], $body
				);

			if (!isset($gitHubResponse->id))
			{
				throw new \Exception('Invalid response from GitHub');
			}

			$data['created_at']  = $gitHubResponse->created_at;
			$data['opened_by']   = $gitHubResponse->user->login;
			$data['number']      = $gitHubResponse->number;
			$data['description'] = $gitHubResponse->body;

			$data['description'] = $gitHub->markdown->render(
					$body,
					'gfm',
					$project->gh_user . '/' . $project->gh_project
				);
		}
		else
		{
			$date = new Date;

			$data['created_at'] = $date->format($database->getDateFormat());
			$data['opened_by']  = $application->getUser()->username;
			$data['number']     = '???';

			$data['description'] = $gitHub->markdown->render($body, 'markdown');
		}

		$data['priority']        = $this->getInput()->getInt('priority');
		$data['build']           = $this->getInput()->getString('build');
		$data['opened_date']     = $data['created_at'];
		$data['project_id']      = $project->project_id;
		$data['issue_number']    = $data['number'];
		$data['description_raw'] = $body;

		// Store the issue
		try
		{
			$model = new IssueModel;
			$model->add($data);
		}
		catch (\Exception $e)
		{
			$application->enqueueMessage($e->getMessage(), 'error');

			$application->redirect(
				$application->get('uri.base.path')
				. 'tracker/' . $project->alias . '/add'
			);
		}

		$application->enqueueMessage('Your issue report has been submitted', 'success');

		$application->redirect(
			$application->get('uri.base.path')
			. 'tracker/' . $project->alias . '/' . $data->number
		);

		return;
	}
}
