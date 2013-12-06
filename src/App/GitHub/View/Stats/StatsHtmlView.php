<?php
/**
 * Part of the Joomla Tracker's GitHub Application
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\GitHub\View\Stats;

use App\Projects\Model\ProjectModel;

use JTracker\Container;
use JTracker\View\AbstractTrackerHtmlView;

/**
 * System statistics view.
 *
 * @since  1.0
 */
class StatsHtmlView extends AbstractTrackerHtmlView
{
	/**
	 * Config object.
	 *
	 * @var    \stdClass
	 * @since  1.0
	 */
	protected $config;

	/**
	 * Method to render the view.
	 *
	 * @throws \DomainException
	 * @throws \Exception
	 * @return  string  The rendered view.
	 *
	 * @since   1.0
	 */
	public function render()
	{
		$app = Container::retrieve('app');
		$gitHub = Container::retrieve('gitHub');

		$project = with(new ProjectModel)->getByAlias($app->input->get('project_alias'));

		$data = false;

		// @todo use the message queue - when it works... or BETTER: Use a controller !
		$message = '';

		try
		{
			$data = $gitHub->repositories->statistics->getListContributors(
				$project->gh_user, $project->gh_project
			);
		}
		catch (\DomainException $e)
		{
			if (202 != $e->getCode())
			{
				throw $e;
			}

			// @todo use the message queue - when it works... or BETTER: Use a controller !
			// Container::retrieve('app')->enqueueMessage($e->getMessage(), 'warning');

			$message = $e->getMessage();
		}

		$this->renderer
			->set('data', $data)
			// @todo use the message queue - when it works... or BETTER: Use a controller !
			->set('message', $message)
			->set('project', $project);

		return parent::render();
	}
}
