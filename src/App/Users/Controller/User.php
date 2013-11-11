<?php
/**
 * Part of the Joomla Tracker's Users Application
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Users\Controller;

use App\Users\Model\UserModel;
use App\Users\View\User\UserHtmlView;
use JTracker\Controller\AbstractTrackerController;

/**
 * User controller class for the users component
 *
 * @since  1.0
 */
class User extends AbstractTrackerController
{
	/**
	 * @var  UserHtmlView
	 */
	protected $view;

	/**
	 * @var  UserModel
	 */
	protected $model;

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

		$id = $this->container->get('app')->input->getUint('id');

		if (!$id)
		{
			throw new \UnexpectedValueException('No id given');
		}

		$this->view->id = $id;

		$this->model->setProject($this->container->get('app')->getProject());
	}
}
