<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Tracker\Controller;

use App\Tracker\Model\IssuesModel;
use App\Tracker\View\Issues\IssuesHtmlView;

use JTracker\Controller\AbstractTrackerListController;

/**
 * Default controller class for the Tracker component.
 *
 * @since  1.0
 */
class DefaultController extends AbstractTrackerListController
{
	/**
	 * @var IssuesHtmlView
	 */
	protected $view;

	/**
	 * @var IssuesModel
	 */
	protected $model;

	/**
	 * The default view for the app
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $defaultView = 'issues';

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

		$this->model->setProject($this->container->get('app')->getProject());
		$this->view->setProject($this->container->get('app')->getProject());
	}

	/**
	 * Execute the controller.
	 *
	 * @return  string  The rendered view.
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		/* @type \JTracker\Application $application */
		$application = $this->container->get('app');

		if ($application->getProject()->project_id)
		{
			$application->getUser()->authorize('view', $application->getProject());
		}

		return parent::execute();
	}
}
