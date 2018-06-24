<?php
/**
 * Part of the Joomla Tracker's GitHub Application
 *
 * @copyright  Copyright (C) 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\GitHub\Controller\Ajax\Milestones;

use JTracker\Github\GithubFactory;

/**
 * Controller class to add new milestones to the GitHub repository.
 *
 * @since  1.0
 */
class Save extends Base
{
	/**
	 * Prepare the response.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	protected function prepareResponse()
	{
		/** @var \JTracker\Application $application */
		$application = $this->getContainer()->get('app');

		$application->getUser()->authorize('manage');

		$number      = $application->input->getUint('number');
		$title       = $application->input->getString('title');
		$state       = $application->input->getCmd('state');
		$description = $application->input->getString('description');
		$due_on      = $application->input->getString('due_on') ? : null;

		$project = $application->getProject();

		// Look if we have a bot user configured.
		if ($project->getGh_Editbot_User() && $project->getGh_Editbot_Pass())
		{
			$gitHub = GithubFactory::getInstance(
				$application, true, $project->getGh_Editbot_User(), $project->getGh_Editbot_Pass()
			);
		}
		else
		{
			$gitHub = GithubFactory::getInstance($application);
		}

		if ($number)
		{
			// Update milestone
			$gitHub->issues->milestones->edit(
				$project->gh_user, $project->gh_project, $number, $title, $state, $description, $due_on
			);
		}
		else
		{
			// Create the milestone.
			$gitHub->issues->milestones->create(
				$project->gh_user, $project->gh_project, $title, $state, $description, $due_on
			);
		}

		// Get the current milestones list.
		$this->response->data = $this->getList($project);
	}
}
