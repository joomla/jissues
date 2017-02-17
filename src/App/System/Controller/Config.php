<?php
/**
 * Part of the Joomla Tracker's Support Application
 *
 * @copyright  Copyright (C) 2012-2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\System\Controller;

use JTracker\Controller\AbstractTrackerController;

/**
 * Controller class to display the application configuration
 *
 * @since  1.0
 */
class Config extends AbstractTrackerController
{
	/**
	 * Execute the controller.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		$this->getContainer()->get('app')->getUser()->authorize('admin');

		return parent::execute();
	}
}
