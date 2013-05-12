<?php
/**
 * @package    JTracker\Components\Users
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tracker\Components\Users\Controller;

use Joomla\Tracker\Controller\AbstractTrackerController;

/**
 * Class LogoutController.
 *
 * @since  1.0
 */
class LogoutController extends AbstractTrackerController
{
	/**
	 * Execute the controller.
	 *
	 * @throws \Exception
	 * @return  string  The rendered view.
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		/* @var \Joomla\Tracker\Application\TrackerApplication $app */
		$app = $this->getApplication();

		// Logout the user.
		$app->setUser();

		$app->redirect('');

		return '';
	}
}
