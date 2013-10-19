<?php
/**
 * Part of the Joomla Tracker's GitHub Application
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\GitHub\Controller\Ajax\Hooks;

use JTracker\Controller\AbstractAjaxController;
use JTracker\Container;

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
		$this->getApplication()->getUser()->authorize('admin');

		$url    = $this->getInput()->getHtml('url');
		$events = $this->getInput()->getHtml('events');

		$project = $this->getApplication()->getProject();

		/* @type \Joomla\Github\Github $github */
		$github = Container::retrieve('gitHub');

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
