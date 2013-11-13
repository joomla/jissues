<?php
/**
 * Part of the Joomla Tracker's Projects Application
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Projects\Controller\Project;

use App\Projects\View\Project\ProjectHtmlView;

use JTracker\Controller\AbstractTrackerController;

/**
 * Controller class to edit a project.
 *
 * @since  1.0
 */
class Edit extends AbstractTrackerController
{
	/**
	 * The default view for the component
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $defaultView = 'project';

	/**
	 * The default layout for the app.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $defaultLayout = 'edit';

	/**
	 * @var  ProjectHtmlView
	 */
	protected $view;

	/**
	 * Initialize the controller.
	 *
	 * This will set up default model and view classes.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	public function initialize()
	{
		parent::initialize();

		$this->container->get('app')->getUser()->authorize('admin');

		$this->view->setAlias($this->container->get('app')->input->get('project_alias'));
	}
}
