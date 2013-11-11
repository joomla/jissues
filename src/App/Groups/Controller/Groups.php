<?php
/**
 * Part of the Joomla Tracker's Groups Application
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
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
	 * @var  GroupsModel
	 */
	protected $model;

	/**
	 * @var  GroupsHtmlView
	 */
	protected $view;

	/**
	 * Initialize the controller.
	 *
	 * @return  $this
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
