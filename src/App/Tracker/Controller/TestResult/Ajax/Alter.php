<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Controller\TestResult\Ajax;

use App\Tracker\Controller\TestResult\AbstractTest;

/**
 * Alter test result controller class.
 *
 * @since  1.0
 */
class Alter extends AbstractTest
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
			throw new \Exception('You are not allowed to alter this item.');
		}

		$itemId  = $application->input->getUint('issueId');

		if (!$itemId)
		{
			throw new \Exception('No issue ID received.');
		}

		$this->response->data = $this->addTest(
			'alter_testresult',
			$itemId,
			$application->input->getUsername('user'),
			$application->input->getUint('result')
		);

		$this->updateStatus($itemId);

		$this->response->message = g11n3t('Test successfully altered');
	}
}
