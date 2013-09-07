<?php
/**
 * Part of the Joomla Tracker's Projects Application
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Projects\Controller\Project;

use App\Tracker\Controller\DefaultController;
use App\Projects\Table\ProjectsTable;
use JTracker\Container;

/**
 * Controller class to save a project.
 *
 * @since  1.0
 */
class SaveController extends DefaultController
{
	/**
	 * The default view for the component
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $defaultView = 'projects';

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

		$app->getUser()->authorize('admin');

		$table = new ProjectsTable(Container::retrieve('db'));

		$table->save($app->input->get('project', array(), 'array'));

		$this->getInput()->set('view', 'projects');

		// Reload the project.
		$app->getProject(true);

		return parent::execute();
	}
}
