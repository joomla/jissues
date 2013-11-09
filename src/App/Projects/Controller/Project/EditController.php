<?php
/**
 * Part of the Joomla Tracker's Projects Application
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Projects\Controller\Project;

use App\Tracker\Controller\DefaultController;

/**
 * Controller class to edit a project.
 *
 * @since  1.0
 */
class EditController extends DefaultController
{
	/**
	 * The default view for the component
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $defaultView = 'project';

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
		$this->container->get('app')->getUser()->authorize('admin');

		$input = $this->container->get('app')->input;

		$input->set('layout', 'edit');
		$input->set('view', 'project');

		parent::initialize();

		$this->view->setAlias($this->container->get('app')->input->get('project_alias'));
	}
}
