<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tracker\Components\System\View\Stats;

use Joomla\Github\Github;
use Joomla\Tracker\Components\Tracker\Model\ProjectModel;
use Joomla\Tracker\View\AbstractTrackerHtmlView;
use Joomla\Utilities\ArrayHelper;

/**
 * Config view.
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
	 * @since   1.0
	 * @throws  \RuntimeException
	 *
	 * @return  string  The rendered view.
	 */
	public function render()
	{
		$projectModel = new ProjectModel;

		$project = $projectModel->getItem();

		$gitHub = new Github;

		$data = $gitHub->repositories->statistics->contributors(
			$project->gh_user, $project->gh_project
		);

		$this->renderer
			->set('data', $data)
			->set('project', $project->getIterator());

		return parent::render();
	}
}
