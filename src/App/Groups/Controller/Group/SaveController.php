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
		$this->container->get('app')->getUser()->authorize('manage');

		$input = $this->container->get('app')->input;

		$group = $input->get('group', array(), 'array');

		$table = new GroupsTable($this->container->get('db'));

		$table->save($group);

		$input->set('view', 'groups');

		return parent::execute();
	}
}
