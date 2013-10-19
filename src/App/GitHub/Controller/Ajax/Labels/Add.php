<?php
/**
 * Part of the Joomla Tracker's GitHub Application
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\GitHub\Controller\Ajax\Labels;

use JTracker\Controller\AbstractAjaxController;
use JTracker\Container;

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
		$this->getApplication()->getUser()->authorize('admin');

		$name  = $this->getInput()->getCmd('name');
		$color = $this->getInput()->getCmd('color');

		$project = $this->getApplication()->getProject();

		/* @type \Joomla\Github\Github $gitHub */
		$gitHub = Container::retrieve('gitHub');

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
