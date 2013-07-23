<?php
/**
 * @package    JTracker\Components\Users
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\GitHub\Controller\Labels\Ajax;

use JTracker\Controller\AbstractTrackerController;

/**
 * Default controller class for the Users component.
 *
 * @package  JTracker\Components\Users
 * @since    1.0
 */
class Add extends AbstractTrackerController
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

			$name  = $this->getInput()->getCmd('name');
			$color = $this->getInput()->getCmd('color');

			$project = $this->getApplication()->getProject();
			$gitHub  = $this->getApplication()->getGitHub();

			// Create the label.
			$gitHub->issues->labels->create(
				$project->gh_user,
				$project->gh_project,
				$name,
				$color
			);

			// Get the current hooks list.
			$response->data = $gitHub->issues->labels->getList(
				$project->gh_user, $project->gh_project
			);
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
