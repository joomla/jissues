<?php
/**
 * Part of the Joomla Tracker's Groups Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
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
	/**
	 * The default layout for the app
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $defaultLayout = 'edit';

	/**
	 * Model object
	 *
	 * @var    GroupModel
	 * @since  1.0
	 */
	protected $model;

	/**
	 * View object
	 *
	 * @var    GroupHtmlView
	 * @since  1.0
	 */
	protected $view;

	/**
	 * Initialize the controller.
	 *
	 * This will set up default model and view classes.
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
		$this->model->setGroupId($this->container->get('app')->input->getInt('group_id'));

		$this->view->setProject($this->container->get('app')->getProject());

		return $this;
	}
}
