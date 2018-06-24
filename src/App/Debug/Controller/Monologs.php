<?php
/**
 * Part of the Joomla! Tracker Application
 *
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Debug\Controller;

use App\Debug\View\Monologs\MonologsHtmlView;

use JTracker\Controller\AbstractTrackerController;

/**
 * Controller class to display Monolog log files.
 *
 * @since  1.0
 */
class Monologs extends AbstractTrackerController
{
	/**
	 * @var  MonologsHtmlView
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

		/** @var \JTracker\Application $application */
		$application = $this->getContainer()->get('app');

		$application->getUser()->authorize('admin');

		$count = $application->input->getInt('c', $application->getSession()->get('logCount', 10));

		$application->getSession()->set('logCount', $count);

		$this->view->setLogType($application->input->get('log_type'));
		$this->view->setCount($count);
		$this->view->setDebugger($this->getContainer()->get('debugger'));

		return $this;
	}
}
