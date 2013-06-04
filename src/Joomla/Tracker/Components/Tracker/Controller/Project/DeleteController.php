<?php
/**
 * @copyright  Copyright (C) 2013 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tracker\Components\Tracker\Controller\Project;

use Joomla\Tracker\Components\Tracker\Controller\DefaultController;
use Joomla\Tracker\Components\Tracker\Model\ProjectModel;
use Joomla\Tracker\Components\Tracker\Table\ProjectsTable;

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
		$app = $this->getApplication();

		$app->getUser()->authorize('admin');

		$model = new ProjectModel;

		$project = $model->getByAlias();

		$table = new ProjectsTable($app->getDatabase());

		$table->delete($project->project_id);

		$this->getInput()->set('view', 'projects');

		parent::execute();
	}
}
