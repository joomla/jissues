<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Controller\Issue;

use App\Tracker\View\Issue\IssueHtmlView;

use JTracker\Controller\AbstractTrackerController;
use JTracker\Github\GithubFactory;

/**
 * Controller class to add an item via the tracker component.
 *
 * @since  1.0
 */
class Add extends AbstractTrackerController
{
	/**
	 * The default view for the component.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $defaultView = 'issue';

	/**
	 * The default view for the component.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $defaultLayout = 'add';

	/**
	 * View object
	 *
	 * @var    IssueHtmlView
	 * @since  1.0
	 */
	protected $view = null;

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

		$this->getContainer()->get('app')->getUser()->authorize('create');

		// Set some defaults
		$item = new \stdClass;
		$item->issue_number    = 0;
		$item->priority        = 3;
		$item->description_raw = $this->fetchTemplate();

		$this->view->setProject($this->getContainer()->get('app')->getProject());
		$this->view->setItem($item);
	}

	/**
	 * Fetch an issue template file from GitHub.
	 *
	 * @return mixed
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	private function fetchTemplate()
	{
		/* @type $project \App\Projects\TrackerProject */
		$project = $this->getContainer()->get('app')->getProject();
		$github = GithubFactory::getInstance($this->getContainer()->get('app'));

		try
		{
			// Check if the template exists in the '.github' folder.
			$fileContents = $github->repositories->contents->get(
				$project->getGh_User(),
				$project->getGh_Project(),
				'.github/ISSUE_TEMPLATE.md'
			);

			return base64_decode($fileContents->content);
		}
		catch (\DomainException $exception)
		{
			try
			{
				// Check if the template exists at the root.
				$fileContents = $github->repositories->contents->get(
					$project->getGh_User(),
					$project->getGh_Project(),
					'ISSUE_TEMPLATE.md'
				);

				return base64_decode($fileContents->content);
			}
			catch (\DomainException $exception)
			{
				// Fall back to the default template.
				$path = JPATH_ROOT . '/src/App/Tracker/tpl/new-issue-template.md';

				if (!file_exists($path))
				{
					throw new \RuntimeException('New issue template not found.');
				}

				return file_get_contents($path);
			}
		}
	}
}
