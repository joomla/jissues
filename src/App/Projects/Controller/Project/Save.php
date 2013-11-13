<?php
/**
 * Part of the Joomla Tracker's Projects Application
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Projects\Controller\Project;

use App\Projects\Model\ProjectsModel;
use App\Projects\Table\ProjectsTable;

use JTracker\Controller\AbstractTrackerController;

/**
 * Controller class to save a project.
 *
 * @since  1.0
 */
class Save extends AbstractTrackerController
{
	/**
	 * The default view for the component
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $defaultView = 'projects';

	/**
	 * @var  ProjectsModel
	 */
	protected $model;

	/**
	 * Initialize the controller.
	 *
	 * This will set up default model and view classes.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	public function initialize()
	{
		$app = $this->container->get('app');

		$app->getUser()->authorize('admin');

		$table = new ProjectsTable($this->container->get('db'));

		$table->save($app->input->get('project', array(), 'array'));

		// Reload the project.
		$app->getProject(true);

		parent::initialize();

		$this->model->setUser($this ->container->get('app')->getUser());
	}
}
