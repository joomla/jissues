<?php
/**
 * Part of the Joomla Tracker's Support Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\System\Controller;

use JTracker\Controller\AbstractTrackerController;

/**
 * Controller class to display a message to individuals looking for the wrong CMS
 *
 * @since  1.0
 */
class WrongCms extends AbstractTrackerController
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
		return "This isn't the CMS you're looking for.";
	}
}
