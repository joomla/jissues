<?php
/**
 * Part of the Joomla Tracker's Projects Application
 *
 * @copyright  Copyright (C) 2012-2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Projects\Controller\Project;

use JTracker\Controller\AbstractTrackerController;

/**
 * Controller class to add a project.
 *
 * @since  1.0
 */
class Add extends AbstractTrackerController
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
	 * Execute the controller.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		$this->getContainer()->get('app')->getUser()->authorize('admin');

		return parent::execute();
	}
}
