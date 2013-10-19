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
 * Controller class to display authorized labels on the GitHub repository.
 *
 * @since  1.0
 */
class GetList extends AbstractAjaxController
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

		$project = $this->getApplication()->getProject();

		/* @type \Joomla\Github\Github $github */
		$github = Container::retrieve('gitHub');

		$this->response->data = $github->issues->labels->getList($project->gh_user, $project->gh_project);
	}
}
