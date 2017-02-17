<?php
/**
 * Part of the Joomla Tracker's GitHub Application
 *
 * @copyright  Copyright (C) 2012-2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\GitHub\Controller;

use JTracker\Controller\AbstractTrackerController;

/**
 * Controller class for managing webhooks
 *
 * @since  1.0
 */
class Hooks extends AbstractTrackerController
{
	/**
	 * Initialize the controller.
	 *
	 * @return  $this  Method allows chaining
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	public function initialize()
	{
		parent::initialize();

		$this->getContainer()->get('app')->getUser()->authorize('admin');

		$this->view->addData('project', $this->getContainer()->get('app')->getProject());

		return $this;
	}
}
