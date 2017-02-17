<?php
/**
 * Part of the Joomla Tracker's Projects Application
 *
 * @copyright  Copyright (C) 2012-2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
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
	 * Model object
	 *
	 * @var    ProjectsModel
	 * @since  1.0
	 */
	protected $model;

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

		$this->model->setUser($this ->getContainer()->get('app')->getUser());

		return $this;
	}

	/**
	 * Execute the controller.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		$app = $this->getContainer()->get('app');

		$app->getUser()->authorize('admin');

		(new ProjectsTable($this->getContainer()->get('db')))
			->save($app->input->get('project', [], 'array'));

		// Reload the project.
		$app->getProject(true);

		return parent::execute();
	}
}
