<?php
/**
 * Part of the Joomla Tracker's GitHub Application
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\GitHub\Controller\Labels\Ajax;

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
		$this->getApplication()->getUser()->authorize('admin');

		$name = $this->getInput()->getCmd('name');

		$project = $this->getApplication()->getProject();

		// Delete the label
		$this->getApplication()->getGitHub()
			->issues->labels->delete(
				$project->gh_user, $project->gh_project, $name
			);

		// Get the current labels list.
		$this->response->data = $this->getApplication()->getGitHub()
			->issues->labels->getList(
				$project->gh_user, $project->gh_project
			);
	}
}
