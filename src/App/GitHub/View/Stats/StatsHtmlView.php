<?php
/**
 * Part of the Joomla Tracker's GitHub Application
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\GitHub\View\Stats;

use App\Projects\Model\ProjectModel;
use Joomla\Github\Github;
use JTracker\View\AbstractTrackerHtmlView;

/**
 * System statistics view.
 *
 * @since  1.0
 */
class StatsHtmlView extends AbstractTrackerHtmlView
{
	/**
	 * @var    \stdClass
	 * @since  1.0
	 */
	protected $config;

	/**
	 * Method to render the view.
	 *
	 * @return  string  The rendered view.
	 *
	 * @since   1.0
	 */
	public function render()
	{
		$projectModel = new ProjectModel;

		$project = $projectModel->getByAlias();

		$gitHub = new Github;

		$data = $gitHub->repositories->statistics->getListContributors(
			$project->gh_user, $project->gh_project
		);

		$this->renderer
			->set('data', $data)
			->set('project', $project);

		return parent::render();
	}
}
