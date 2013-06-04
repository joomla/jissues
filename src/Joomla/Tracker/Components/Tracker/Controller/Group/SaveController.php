<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tracker\Components\Tracker\Controller\Group;

use Joomla\Tracker\Components\Tracker\Controller\DefaultController;
use Joomla\Tracker\Components\Tracker\Table\GroupsTable;

/**
 * Controller class to add an item via the tracker component.
 *
 * @package  JTracker\Components\Tracker
 * @since    1.0
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

		$table = new GroupsTable($this->getApplication()->getDatabase());

		$table->save($group);

		$this->getInput()->set('view', 'groups');

		return parent::execute();
	}
}
