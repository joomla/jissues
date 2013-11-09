<?php
/**
 * Part of the Joomla Tracker's Users Application
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Users\Controller;

use JTracker\Application;
use JTracker\Controller\AbstractTrackerController;

/**
 * Logout controller class for the users component
 *
 * @since  1.0
 */
class Logout extends AbstractTrackerController
{
	/**
	 * @var  Application
	 */
	private $application;

	/**
	 * Execute the controller.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		$application = $this->container->get('app');

		$user = $application->getUser();

		if ($user->id)
		{
			// The user is already logged in.
			$application->redirect(' ');

			return;
		}

		// Logout the user.
		$application->setUser();

		$application->redirect(' ');
	}

	/**
	 * @throws \UnexpectedValueException
	 * @return \JTracker\Application
	 */
	public function getApplication()
	{
		if (is_null($this->application))
		{
			throw new \UnexpectedValueException('Application not set');
		}

		return $this->application;
	}

	/**
	 * @param \JTracker\Application $application
	 */
	public function setApplication(Application $application)
	{
		$this->application = $application;
	}
}
