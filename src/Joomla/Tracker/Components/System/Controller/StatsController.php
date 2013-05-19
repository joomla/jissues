<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tracker\Components\System\Controller;

use Joomla\Factory;
use Joomla\Tracker\Components\Tracker\Model\ProjectModel;
use Joomla\Tracker\Controller\AbstractTrackerController;

/**
 * Controller class to display project statistics
 *
 * @since  1.0
 */
class StatsController extends AbstractTrackerController
{
	/**
	 * The default view for the component
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $defaultView = 'stats';

	/**
	 * Execute the controller.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		$projectAlias = $this->getInput()->get('project_alias');

		$projectModel = new ProjectModel;

		$project = $projectModel->getByAlias($projectAlias);

		if ($project)
		{
			$this->getInput()->set('project_id', $project->project_id);
		}
		else
		{
			// No project... CRY :(
		}

		parent::execute();
	}
}
