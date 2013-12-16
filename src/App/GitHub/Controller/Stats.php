<?php
/**
 * Part of the Joomla Tracker's GitHub Application
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\GitHub\Controller;

use App\GitHub\View\Stats\StatsHtmlView;

use Joomla\Github\Github;

use JTracker\Controller\AbstractTrackerController;

/**
 * Controller class to display project statistics
 *
 * @since  1.0
 */
class Stats extends AbstractTrackerController
{
	/**
	 * @var  StatsHtmlView
	 */
	protected $view;

	/**
	 * Initialize the controller.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	public function initialize()
	{
		parent::initialize();

		$project = $this->container->get('app')->getProject();

		$gitHub = new Github;

		$data = $gitHub->repositories->statistics->getListContributors(
			$project->gh_user, $project->gh_project
		);

		$this->view->setProject($project);
		$this->view->setData($data);

		return $this;
	}
}
