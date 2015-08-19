<?php
/**
 * Part of the Joomla Tracker's Projects Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Projects\Controller\Project;

use App\Projects\Model\ProjectModel;
use App\Projects\Model\ProjectsModel;

use JTracker\Controller\AbstractTrackerController;

/**
 * Controller class to delete a project.
 *
 * @since  1.0
 */
class Delete extends AbstractTrackerController
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

		$this->getContainer()->get('app')->getUser()->authorize('admin');

		$this->model->setUser($this->getContainer()->get('app')->getUser());

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
		$model = new ProjectModel($this->getContainer()->get('db'));

		$model->delete($this->getContainer()->get('app')->input->get('project_alias'));

		// Reload the project
		$this->getContainer()->get('app')->getProject(true);

		return parent::execute();
	}
}
