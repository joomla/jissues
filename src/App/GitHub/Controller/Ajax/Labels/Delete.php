<?php
/**
 * Part of the Joomla Tracker's GitHub Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\GitHub\Controller\Ajax\Labels;

use JTracker\Controller\AbstractAjaxController;

/**
 * Controller class to delete labels from the GitHub repository.
 *
 * @since  1.0
 */
class Delete extends AbstractAjaxController
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
		$this->getContainer()->get('app')->getUser()->authorize('admin');

		$name = $this->getContainer()->get('app')->input->getCmd('name');

		$project = $this->getContainer()->get('app')->getProject();

		/* @type \Joomla\Github\Github $github */
		$github = $this->getContainer()->get('gitHub');

		// Delete the label
		$github->issues->labels->delete($project->gh_user, $project->gh_project, $name);

		// Get the current labels list.
		$this->response->data = $github->issues->labels->getList($project->gh_user, $project->gh_project);
	}
}
