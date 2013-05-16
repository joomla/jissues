<?php
/**
 * @copyright  Copyright (C) 2013 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tracker\Components\Tracker\Controller\Project;

use Joomla\Tracker\Components\Tracker\Controller\DefaultController;
use Joomla\Tracker\Components\Tracker\Table\ProjectsTable;

/**
 * Controller class to add an item via the tracker component.
 *
 * @since  1.0
 */
class DeleteController extends DefaultController
{
	protected $defaultView = 'projects';

	/**
	 * Execute the controller.
	 *
	 * @since  1.0
	 *
	 * @return void
	 */
	public function execute()
	{
		$app = $this->getApplication();

		$table = new ProjectsTable($app->getDatabase());

		$table->delete($app->input->getInt('project_id'));

		$this->getInput()->set('view', 'projects');

		parent::execute();
	}
}
