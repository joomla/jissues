<?php
/**
 * Part of the Joomla Tracker's Groups Application
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Groups\Controller\Group;

use App\Groups\Table\GroupsTable;
use App\Tracker\Controller\DefaultController;
use JTracker\Container;

/**
 * Controller class to save a group.
 *
 * @since  1.0
 */
class SaveController extends DefaultController
{
	/**
	 * Execute the controller.
	 *
	 * @return  string  The rendered view.
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		$this->getApplication()->getUser()->authorize('manage');

		$input = $this->getInput();

		$group = $input->get('group', array(), 'array');

		$table = new GroupsTable(Container::retrieve('db'));

		$table->save($group);

		$this->getInput()->set('view', 'groups');

		return parent::execute();
	}
}
