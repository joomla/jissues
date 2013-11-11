<?php
/**
 * Part of the Joomla Tracker's Debug Application
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
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
	 * @return  $this
	 *
	 * @since   1.0
	 */
	public function initialize()
	{
		parent::initialize();

		$this->container->get('app')->getUser()->authorize('admin');

		$this->view->setLogType($this->container->get('app')->input->get('log_type'));
		$this->view->setDebugger(new TrackerDebugger($this->container));

		return $this;
	}
}
