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
use JTracker\Controller\AbstractTrackerController;

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

	public function initialize()
	{
		$app = $this->container->get('app');

		$app->getUser()->authorize('admin');

		$table = new ProjectsTable($this->container->get('db'));

		$table->save($app->input->get('project', array(), 'array'));

		$this->container->get('app')->input->set('view', 'projects');

		// Reload the project.
		$app->getProject(true);

		parent::initialize();

		$this->model->setUser($this ->container->get('app')->getUser());
	}
}
