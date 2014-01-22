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
		$this->container->get('app')->getUser()->authorize('admin');

		$name  = $this->container->get('app')->input->getCmd('name');
		$color = $this->container->get('app')->input->getCmd('color');

		$project = $this->container->get('app')->getProject();

		/* @type \Joomla\Github\Github $gitHub */
		$gitHub = $this->container->get('gitHub');

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
