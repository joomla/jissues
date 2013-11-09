<?php
/**
 * Part of the Joomla Tracker's Groups Application
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Groups\Controller\Group;

use App\Tracker\Controller\DefaultController;

/**
 * Controller class to add a group.
 *
 * @since  1.0
 */
class AddController extends DefaultController
{
	/**
	 * The default view for the component.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $defaultView = 'group';

	/**
	 * Execute the controller.
	 *
	 * @return  string  The rendered view.
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		$this->container->get('app')->getUser()->authorize('manage');

		$this->container->get('app')->input->set('layout', 'edit');
		$this->container->get('app')->input->set('view', 'group');

		return parent::execute();
	}
}
