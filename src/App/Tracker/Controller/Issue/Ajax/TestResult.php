<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Controller\Issue\Ajax;

use App\Tracker\Model\IssueModel;

use JTracker\Controller\AbstractAjaxController;

/**
 * Add test result controller class.
 *
 * @since  1.0
 */
class TestResult extends AbstractAjaxController
{
	/**
	 * Prepare the response.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 * @throws  \Exception
	 */
	protected function prepareResponse()
	{
		/* @type \JTracker\Application $application */
		$application = $this->getContainer()->get('app');
		$user        = $application->getUser();

		if (!$user->check('edit'))
		{
			throw new \Exception('You are not allowed to test this item.');
		}

		$issueId  = $application->input->getUint('issueId');
		$result   = $application->input->getUint('result');

		if (!$issueId)
		{
			throw new \Exception('No issue ID received.');
		}

		$model = new IssueModel($this->getContainer()->get('db'));

		$this->response->data = json_encode($model->saveTest($issueId, $user->username, $result));

		$this->response->message = g11n3t('Test successfully added');
	}
}
