<?php
/**
 * Part of the Joomla Tracker's GitHub Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\GitHub\Controller\Ajax\Labels;

use JTracker\Controller\AbstractAjaxController;
use JTracker\Github\GithubFactory;

/**
 * Controller class to add new labels to the GitHub repository.
 *
 * @since  1.0
 */
class Add extends AbstractAjaxController
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

		$name  = $application->input->getString('name');
		$color = $application->input->getCmd('color');

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

		// Create the label.
		$gitHub->issues->labels->create(
			$project->gh_user,
			$project->gh_project,
			$name,
			$color
		);

		// Get the current hooks list.
		$this->response->data = $gitHub->issues->labels->getList(
			$project->gh_user, $project->gh_project
		);
	}
}
