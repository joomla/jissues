<?php
/**
 * Part of the Joomla Tracker's GitHub Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\GitHub\Controller\Ajax\Milestones;

use JTracker\Controller\AbstractAjaxController;

/**
 * Controller class to display milestones on the GitHub repository.
 *
 * @since  1.0
 */
class GetList extends AbstractAjaxController
{
	/**
	 * Prepare the response.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	protected function prepareResponse()
	{
		$this->getContainer()->get('app')->getUser()->authorize('manage');

		$project = $this->getContainer()->get('app')->getProject();

		/* @type \Joomla\Github\Github $github */
		$gitHub = $this->getContainer()->get('gitHub');

		$data = $gitHub->issues->milestones->getList($project->gh_user, $project->gh_project);

		$milestones = [];

		foreach ($data as $item)
		{
			// This is to keep request data short..

			$milestone = new \stdClass;

			$milestone->number      = $item->number;
			$milestone->title       = $item->title;
			$milestone->state       = $item->state;
			$milestone->description = $item->description;
			$milestone->due_on      = $item->due_on;

			$milestones[] = $milestone;
		}

		usort(
			$milestones, function ($a, $b)
			{
				return $a->number > $b->number;
			}
		);

		$this->response->data = $milestones;
	}
}
