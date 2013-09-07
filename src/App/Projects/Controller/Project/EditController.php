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
	 * Execute the controller.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		$this->getApplication()->getUser()->authorize('admin');

		$input = $this->getInput();

		$input->set('layout', 'edit');
		$input->set('view', 'project');

		return parent::execute();
	}
}
