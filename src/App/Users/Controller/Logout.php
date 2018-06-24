<?php
/**
 * Part of the Joomla Tracker's Users Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
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
	 * Execute the controller.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		/** @var Application $application */
		$application = $this->getContainer()->get('app');

		// Invalidate the session
		$application->getSession()->invalidate();

		$application
			// Logout the user.
			->setUser(null)
			// Delete the "remember me" cookie
			->setRememberMe(false)
			// Redirect to the "home" page
			->redirect(' ');
	}
}
