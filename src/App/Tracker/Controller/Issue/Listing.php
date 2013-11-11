<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Tracker\Controller\Issue;

use App\Tracker\View\Issues\IssuesHtmlView;
use App\Tracker\Model\IssuesModel;

use JTracker\Controller\AbstractTrackerController;

/**
 * List controller class for the Tracker component.
 *
 * @since  1.0
 */
class Listing extends AbstractTrackerController
{
	/**
	 * The default view for the component
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $defaultView = 'issues';

	/**
	 * @var  IssuesHtmlView
	 */
	protected $view = null;

	/**
	 * @var  IssuesModel
	 */
	protected $model = null;

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

		$this->container->get('app')->getUser()->authorize('view');

		$this->model->setProject($this->container->get('app')->getProject());
		$this->view->setProject($this->container->get('app')->getProject());

		return $this;
	}
}
