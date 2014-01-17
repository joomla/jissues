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
 * Controller class to add new webhooks to the GitHub repository.
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

		$url    = $this->container->get('app')->input->getHtml('url');
		$events = $this->container->get('app')->input->getHtml('events');

		$project = $this->container->get('app')->getProject();

		/* @type \Joomla\Github\Github $gitHub */
		$gitHub = $this->container->get('gitHub');

		$name   = 'web';
		$active = 1;

		$config = array(
			'url'          => $url,
			'content-type' => 'json'
		);

		// Create the hook.
		$gitHub->repositories->hooks->create(
			$project->gh_user,
			$project->gh_project,
			$name,
			$config,
			explode(',', $events),
			$active
		);

		// Get the current hooks list.
		$this->response->data = $gitHub->repositories->hooks->getList($project->gh_user, $project->gh_project);
	}
}
