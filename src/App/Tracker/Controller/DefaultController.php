<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
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
	 * View object
	 *
	 * @var    IssuesHtmlView
	 * @since  1.0
	 */
	protected $view;

	/**
	 * Model object
	 *
	 * @var    IssuesModel
	 * @since  1.0
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
	 * @return  $this  Method supports chaining
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
