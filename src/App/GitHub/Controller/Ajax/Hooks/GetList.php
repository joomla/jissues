<?php
/**
 * Part of the Joomla Tracker's GitHub Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\GitHub\Controller\Ajax\Hooks;

use JTracker\Controller\AbstractAjaxController;

/**
 * Controller class to display authorized webhooks on the GitHub repository.
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
		$this->getContainer()->get('app')->getUser()->authorize('admin');

		$project = $this->getContainer()->get('app')->getProject();

		/** @var \Joomla\Github\Github $github */
		$github = $this->getContainer()->get('gitHub');

		$this->response->data = $github->repositories->hooks->getList($project->gh_user, $project->gh_project);
	}
}
