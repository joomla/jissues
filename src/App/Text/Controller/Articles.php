<?php
/**
 * Part of the Joomla Tracker's Text Application
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Text\Controller;

use JTracker\Controller\AbstractTrackerController;

/**
 * Controller class for the Text component.
 *
 * @since  1.0
 */
class Articles extends AbstractTrackerController
{
	/**
	 * Execute the controller.
	 *
	 * @return  string  The rendered view.
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		$this->container->get('app')->getUser()->authorize('admin');

		parent::execute();
	}
}
