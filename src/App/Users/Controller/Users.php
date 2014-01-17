<?php
/**
 * Part of the Joomla Tracker's Users Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Users\Controller;

use JTracker\Controller\AbstractTrackerListController;

/**
 * Users controller class for the users component
 *
 * @since  1.0
 */
class Users extends AbstractTrackerListController
{
	/**
	 * Initialize the controller.
	 *
	 * @return  $this  Method allows chaining
	 *
	 * @since   1.0
	 */
	public function initialize()
	{
		parent::initialize();

		// @todo Setup filters here (if needed)

		return $this;
	}
}
