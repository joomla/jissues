<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Tracker\Controller\Issue;

use App\Tracker\Model\IssueModel;

use JTracker\Controller\AbstractTrackerController;

/**
 * Random item controller class for the Tracker component.
 *
 * @since  1.0
 */
class Random extends AbstractTrackerController
{
	/**
	 * Execute the controller.
	 *
	 * @return  void  Redirects the application
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	public function execute()
	{
		$application = $this->getContainer()->get('app');

		$application->getUser()->authorize('view');

		try
		{
			$randomNumber = (new IssueModel($this->getContainer()->get('db')))
				->setProject($this->getContainer()->get('app')->getProject())
				->getRandomNumber();

			$application->redirect(
				$application->get('uri.base.path') . '/tracker/' . $application->input->get('project_alias') . '/' . $randomNumber
			);
		}
		catch (\Exception $e)
		{
			$application->enqueueMessage($e->getMessage(), 'error');

			$application->redirect(
				$application->get('uri.base.path') . 'tracker/' . $application->input->get('project_alias')
			);
		}
	}
}
