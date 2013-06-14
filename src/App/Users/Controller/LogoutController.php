<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Users\Controller;

use JTracker\Controller\AbstractTrackerController;

/**
 * Logout controller class for the users component
 *
 * @since  1.0
 */
class LogoutController extends AbstractTrackerController
{
	/**
	 * Execute the controller.
	 *
	 * @return  string  The rendered view.
	 *
	 * @since   1.0
	 * @throws  \Exception
	 */
	public function execute()
	{
		$app = $this->getApplication();

		// Logout the user.
		$app->setUser();

		$app->redirect(' ');

		return '';
	}
}
