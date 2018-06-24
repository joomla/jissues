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

		/** @var \JTracker\Application $application */
		$application = $this->getContainer()->get('app');

		$state = $this->model->getState();

		$state->set('filter.search-user',
			$application->getUserStateFromRequest('filter.search-user', 'search-user', '', 'string')
		);

		return $this;
	}
}
