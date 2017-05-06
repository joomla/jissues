<?php
/**
 * Part of the Joomla Tracker's Groups Application
 *
 * @copyright  Copyright (C) 2012-2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Groups\Controller\Group;

use App\Groups\Model\GroupModel;
use App\Groups\Table\GroupsTable;

use App\Groups\View\Group\GroupHtmlView;
use JTracker\Controller\AbstractTrackerController;

/**
 * Controller class to save a group.
 *
 * @since  1.0
 */
class Save extends AbstractTrackerController
{
	/**
	 * The default view for the app.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $defaultView = 'groups';

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
	 * @return  $this  Method allows chaining
	 *
	 * @since   1.0
	 */
	public function initialize()
	{
		parent::initialize();

		$this->getContainer()->get('app')->getUser()->authorize('manage');

		$this->model->setProject($this->getContainer()->get('app')->getProject());
		$this->view->setProject($this->getContainer()->get('app')->getProject());

		return $this;
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
		$group = $this->getContainer()->get('app')->input->get('group', [], 'array');

		(new GroupsTable($this->getContainer()->get('db')))
			->save($group);

		return parent::execute();
	}
}
