<?php
/**
 * Part of the Joomla Tracker's Projects Application
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Projects\Controller\Project;

use App\Tracker\Controller\DefaultController;
use App\Projects\Model\ProjectModel;
use App\Projects\Table\ProjectsTable;

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
		$app = $this->container->get('app');

		$app->getUser()->authorize('admin');

		$model = new ProjectModel($this->container->get('db'));

		$project = $model->getByAlias();

		$table = new ProjectsTable($this->container->get('db'));

		$table->delete($project->project_id);

		// Reload the project
		$this->container->get('app')->getProject(true);

		$this->container->get('app')->input->set('view', 'projects');

		parent::execute();
	}
}
