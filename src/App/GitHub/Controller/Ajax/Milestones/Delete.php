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
 * Controller class to delete milestones from the GitHub repository.
 *
 * @since  1.0
 */
class Delete extends Base
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

		$milestoneId = $application->input->getUint('milestone_id');

		$project = $application->getProject();

		// Look if we have a bot user configured.
		if ($project->getGh_Editbot_User() && $project->getGh_Editbot_Pass())
		{
			$gitHub = GithubFactory::getInstance($application, true, $project->getGh_Editbot_User(), $project->getGh_Editbot_Pass());
		}
		else
		{
			$gitHub = GithubFactory::getInstance($application);
		}

		// Delete the milestone
		$gitHub->issues->milestones->delete($project->gh_user, $project->gh_project, $milestoneId);

		// Get the current milestones list.
		$this->response->data = $this->getList($project);
	}
}
