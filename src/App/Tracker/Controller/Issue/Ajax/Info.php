<?php
/**
 * @copyright  Copyright (C) 2013 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Tracker\Controller\Issue\Ajax;

use App\Tracker\Model\IssueModel;
use Joomla\Factory;
use JTracker\Controller\AbstractTrackerController;

/**
 * Default controller class for the Users component.
 *
 * @package  JTracker\Components\Users
 * @since    1.0
 */
class Info extends AbstractTrackerController
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
			/* @type \JTracker\Application\TrackerApplication $application */
			$application = Factory::$application;

			$id = $application->input->getUint('id');

			if (!$id)
			{
				throw new \RuntimeException('No id');
			}

			$model = new IssueModel;

			$item = $model->getItem($id);

			$issue = new \stdClass;

			$issue->comment_count = 0;
			$issue->opened_by = $item->opened_by ? : 'n/a';

			foreach ($item->activities as $activity)
			{
				switch ($activity->event)
				{
					case 'comment':
						$issue->comment_count ++;
					break;

					default :

					break;
				}
			}

			$response->data = $issue;
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
