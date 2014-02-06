<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Controller\Issue;

use App\Tracker\Model\IssueModel;

use Joomla\Date\Date;

use JTracker\Controller\AbstractTrackerController;

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
		/* @type \JTracker\Application $application */
		$application = $this->container->get('app');

		/* @type \Joomla\Github\Github $gitHub */
		$gitHub = $this->container->get('gitHub');

		$project = $application->getProject();

		$body = $application->input->get('body', '', 'raw');

		// Prepare issue for the store
		$data = array();

		$data['title'] = $application->input->getString('title');

		if (!$body)
		{
			throw new \Exception('No body received.');
		}

		if ($project->gh_user && $project->gh_project)
		{
			// Project is managed on GitHub
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

			// $data['description'] = $gitHubResponse->body;

			$data['description'] = $gitHub->markdown->render(
					$body,
					'gfm',
					$project->gh_user . '/' . $project->gh_project
				);
		}
		else
		{
			// Project is managed by JTracker only
			$data['created_at'] = (new Date)->format($this->container->get('db')->getDateFormat());
			$data['opened_by']  = $application->getUser()->username;
			$data['number']     = '???';

			$data['description'] = $gitHub->markdown->render($body, 'markdown');
		}

		$data['priority']        = $application->input->getInt('priority');
		$data['build']           = $application->input->getString('build');
		$data['opened_date']     = $data['created_at'];
		$data['project_id']      = $project->project_id;
		$data['issue_number']    = $data['number'];
		$data['description_raw'] = $body;

		// Store the issue
		try
		{
			$model = new IssueModel($this->container->get('db'));
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
			. 'tracker/' . $project->alias . '/' . $data['number']
		);

		return;
	}
}
