<?php
/**
 * Part of the Joomla Tracker's Activity Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Activity\Controller;

use App\Activity\View\DefaultHtmlView;

use JTracker\Controller\AbstractTrackerController;

/**
 * Base controller class for the Activity application.
 *
 * @since  1.0
 */
abstract class AbstractBaseController extends AbstractTrackerController
{
	/**
	 * View object
	 *
	 * @var    DefaultHtmlView
	 * @since  1.0
	 */
	protected $view;

	/**
	 * Initialize the controller.
	 *
	 * @return  $this  Method supports chaining
	 *
	 * @since   1.0
	 */
	public function initialize()
	{
		parent::initialize();

		/** @var \JTracker\Application $application */
		$application = $this->getContainer()->get('app');

		$application->getUser()->authorize('view');

		$this->model->setProject($application->getProject());
		$this->view->setProject($application->getProject());

		return $this;
	}
}
