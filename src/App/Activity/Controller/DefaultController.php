<?php
/**
 * Part of the Joomla Tracker's Activity Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Activity\Controller;

use App\Activity\View\Activity\ActivityHtmlView;

use JTracker\Controller\AbstractTrackerListController;

/**
 * Default controller class for the Activity application.
 *
 * @since  1.0
 */
class DefaultController extends AbstractTrackerListController
{
	/**
	 * The default view for the app
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $defaultView = 'activity';

	/**
	 * View object
	 *
	 * @var    ActivityHtmlView
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

		/* @type \JTracker\Application $application */
		$application = $this->getContainer()->get('app');

		$application->getUser()->authorize('view');

		$this->model->setProject($application->getProject());
		$this->view->setProject($application->getProject());

		return $this;
	}
}
