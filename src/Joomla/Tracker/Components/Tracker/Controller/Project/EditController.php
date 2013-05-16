<?php
/**
 * @copyright  Copyright (C) 2013 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tracker\Components\Tracker\Controller\Project;

use Joomla\Tracker\Components\Tracker\Controller\DefaultController;

/**
 * Controller class to add an item via the tracker component.
 *
 * @package  JTracker\Components\Tracker
 * @since    1.0
 */
class EditController extends DefaultController
{
	protected $defaultView = 'project';

	/**
	 * Execute the controller.
	 *
	 * @return  string  The rendered view.
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
