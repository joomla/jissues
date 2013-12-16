<?php
/**
 * Part of the Joomla Tracker's Groups Application
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Groups\Controller;

use App\Groups\Model\GroupModel;
use App\Groups\View\Group\GroupHtmlView;

use JTracker\Controller\AbstractTrackerController;

/**
 * Controller class to manage a user group.
 *
 * @package  JTracker\Components\Tracker
 * @since    1.0
 */
class Group extends AbstractTrackerController
{
	protected $defaultLayout = 'edit';

	/**
	 * @var  GroupModel
	 */
	protected $model;

	/**
	 * @var  GroupHtmlView
	 */
	protected $view;

	/**
	 * Initialize the controller.
	 *
	 * This will set up default model and view classes.
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
		$this->model->setGroupId($this->container->get('app')->input->getInt('group_id'));

		$this->view->setProject($this->container->get('app')->getProject());

		return $this;
	}
}
