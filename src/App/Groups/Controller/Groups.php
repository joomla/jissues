<?php
/**
 * Part of the Joomla Tracker's Groups Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Groups\Controller;

use App\Groups\Model\GroupsModel;
use App\Groups\View\Groups\GroupsHtmlView;

use JTracker\Controller\AbstractTrackerController;

/**
 * Controller class to manage the application usergroups.
 *
 * @since  1.0
 */
class Groups extends AbstractTrackerController
{
	/**
	 * Model object
	 *
	 * @var    GroupsModel
	 * @since  1.0
	 */
	protected $model;

	/**
	 * View object
	 *
	 * @var    GroupsHtmlView
	 * @since  1.0
	 */
	protected $view;

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

		$this->container->get('app')->getUser()->authorize('manage');

			$this->model->setProject($this->container->get('app')->getProject());
		$this->view->setProject($this->container->get('app')->getProject());

		return $this;
	}
}
