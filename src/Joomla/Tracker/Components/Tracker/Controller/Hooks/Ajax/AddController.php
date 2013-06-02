<?php
/**
 * @package    JTracker\Components\Users
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tracker\Components\Tracker\Controller\Hooks\Ajax;

use Joomla\Factory;
use Joomla\Tracker\Controller\AbstractTrackerController;

/**
 * Default controller class for the Users component.
 *
 * @package  JTracker\Components\Users
 * @since    1.0
 */
class AddController extends AbstractTrackerController
{
	/**
	 * Execute the controller.
	 *
	 * @since  1.0
	 *
	 * @return  boolean
	 */
	public function execute()
	{
		$response = new \stdClass;

		$response->data  = new \stdClass;
		$response->error = '';
		$response->message = '';

		ob_start();

		try
		{
			$this->getApplication()->getUser()->authorize('admin');

			$url = $this->getInput()->getHtml('url');
			$events = $this->getInput()->getHtml('events');

			$project = $this->getApplication()->getProject();

			$gitHub = $this->getApplication()->getGitHub();

			$name = 'web';

			$config = array();
			$config['url'] = $url;
			$config['content-type'] = 'json';

			$events = explode(',', $events);

			$active = 1;

			$gitHub->repositories->hooks->create(
				$project->gh_user,
				$project->gh_project,
				$name,
				$config,
				$events,
				$active
			);

			$response->data = $gitHub->repositories->hooks->getList($project->gh_user, $project->gh_project);
		}
		catch (\Exception $e)
		{
			$response->error = $e->getMessage();
		}

		$errors = ob_get_clean();

		if ($errors)
		{
			$response->error .= $errors;
		}

		header('Content-type: application/json');

		echo json_encode($response);

		exit(0);
	}
}
