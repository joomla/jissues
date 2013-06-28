<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\GitHub\Controller\Ajax\Markdown;

use JTracker\Controller\AbstractTrackerController;

/**
 * Default controller class for the Tracker component.
 *
 * @since  1.0
 */
class Preview extends AbstractTrackerController
{
	/**
	 * Execute the controller.
	 *
	 * @since  1.0
	 *
	 * @throws \Exception
	 * @return  boolean
	 */
	public function execute()
	{
		$response = new \stdClass;

		$response->data    = new \stdClass;
		$response->error   = '';
		$response->message = '';

		ob_start();

		try
		{
			// Only registered users are able to use the preview
			// using their credentials.

			if (!$this->getApplication()->getUser()->id)
			{
				throw new \Exception('not auth..');
			}

			$text = $this->getInput()->get('text', '', 'raw');

			if (!$text)
			{
				throw new \Exception('Nothing to preview...');
			}

			$project = $this->getApplication()->getProject();

			$response->data = $this->getApplication()->getGitHub()->markdown
				->render(
					$text,
					'gfm',
					$project->gh_user . '/' . $project->gh_project
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
