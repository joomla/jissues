<?php
/**
 * Part of the Joomla Tracker's Debug Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Debug\Controller;

use App\Debug\TrackerDebugger;
use App\Debug\View\Logs\LogsHtmlView;

use JTracker\Controller\AbstractTrackerController;

/**
 * Controller class to display the application configuration
 *
 * @since  1.0
 */
class Logs extends AbstractTrackerController
{
	/**
	 * @var  LogsHtmlView
	 */
	protected $view = null;

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

		$this->getContainer()->get('app')->getUser()->authorize('admin');

		$this->view->setLogType($this->getContainer()->get('app')->input->get('log_type'));
		$this->view->setDebugger(new TrackerDebugger($this->getContainer()));

		return $this;
	}
}
