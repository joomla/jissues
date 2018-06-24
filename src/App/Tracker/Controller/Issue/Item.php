<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Controller\Issue;

use App\Tracker\Model\IssueModel;
use App\Tracker\View\Issue\IssueHtmlView;

use JTracker\Controller\AbstractTrackerController;

/**
 * Item controller class for the Tracker component.
 *
 * @since  1.0
 */
class Item extends AbstractTrackerController
{
	/**
	 * The default view for the component
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $defaultView = 'issue';

	/**
	 * View object
	 *
	 * @var    IssueHtmlView
	 * @since  1.0
	 */
	protected $view = null;

	/**
	 * Model object
	 *
	 * @var    IssueModel
	 * @since  1.0
	 */
	protected $model = null;

	/**
	 * Initialize the controller.
	 *
	 * This will set up default model and view classes.
	 *
	 * @return  $this  Method supports chaining
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	public function initialize()
	{
		parent::initialize();

		/** @var \JTracker\Application $application */
		$application = $this->getContainer()->get('app');
		$project = $application->getProject();
		$user = $application->getUser();

		$user->authorize('view');

		$this->model->setProject($project);

		$item = $this->model->getItem($application->input->getUint('id'));

		$sha = false;

		if (!empty($item->commits))
		{
			$commits = json_decode($item->commits);
			$lastCommit = end($commits);

			if ($lastCommit)
			{
				$sha = $lastCommit->sha;
			}
		}

		$item->userTest = $this->model->getUserTest($item->id, $user->username, $sha);
		$this->view->setItem($item);
		$this->view->setEditOwn($user->canEditOwn($item->opened_by));
		$this->view->setProject($project);

		return $this;
	}
}
