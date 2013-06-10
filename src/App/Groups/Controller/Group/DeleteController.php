<?php
/**
 * @copyright  Copyright (C) 2013 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Groups\Controller\Group;

use App\Groups\Table\GroupsTable;
use App\Tracker\Controller\DefaultController;

/**
 * Controller class to delete a project.
 *
 * @since  1.0
 */
class DeleteController extends DefaultController
{
	/**
	 * The default view for the component
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $defaultView = 'groups';

	/**
	 * Execute the controller.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		$app = $this->getApplication();

		$app->getUser()->authorize('manage');

		$table = new GroupsTable($app->getDatabase());

		$table->load($app->input->getInt('group_id'))
			->delete();

		$this->getInput()->set('view', 'groups');

		parent::execute();
	}
}
