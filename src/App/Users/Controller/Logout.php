<?php
/**
 * Part of the Joomla Tracker's Users Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Users\Controller;

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
		$application = $this->container->get('app');

		// Logout the user.
		$application->setUser(null);

		$application->redirect(' ');
	}
}
