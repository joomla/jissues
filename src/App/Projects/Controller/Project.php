<?php
/**
 * Part of the Joomla Tracker's Projects Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Projects\Controller;

use App\Projects\View\Project\ProjectHtmlView;

use JTracker\Controller\AbstractTrackerController;

/**
 * Controller class for the project view
 *
 * @since  1.0
 */
class Project extends AbstractTrackerController
{
	/**
	 * View object
	 *
	 * @var    ProjectHtmlView
	 * @since  1.0
	 */
	protected $view;

	/**
	 * Initialize the controller.
	 *
	 * This will set up default model and view classes.
	 *
	 * @return  $this  Method allows chaining
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	public function initialize()
	{
		// Reload the project.
		$this->getContainer()->get('app')->getProject(true);

		parent::initialize();

		$this->view->setAlias($this->getContainer()->get('app')->input->get('project_alias'));

		return $this;
	}
}
