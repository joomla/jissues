<?php
/**
 * Part of the Joomla Tracker's Users Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Users\Controller;

use JTracker\Controller\AbstractTrackerController;

/**
 * Users controller class for the users component
 *
 * @since  1.0
 */
class Users extends AbstractTrackerController
{
	/**
	 * View object
	 *
	 * @var    \App\Users\View\Users\UsersHtmlView
	 * @since  1.0
	 */
	protected $view;

	/**
	 * Model object
	 *
	 * @var    \App\Users\Model\UserModel
	 * @since  1.0
	 */
	protected $model;

	/**
	 * Execute the controller.
	 *
	 * @return  string  The rendered view.
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		// $this->getContainer()->get('app')->getUser()->authorize('admin');

		$this->view->setItems($this->model->getItems());

		return parent::execute();
	}
}
