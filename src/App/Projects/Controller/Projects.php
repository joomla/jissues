<?php
/**
 * Part of the Joomla Tracker's Projects Application
 *
 * @copyright  Copyright (C) 2012-2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Projects\Controller;

use App\Projects\Model\ProjectsModel;

use JTracker\Controller\AbstractTrackerController;

/**
 * Controller class for the projects view
 *
 * @since  1.0
 */
class Projects extends AbstractTrackerController
{
	/**
	 * Model object
	 *
	 * @var    ProjectsModel
	 * @since  1.0
	 */
	protected $model;

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
		parent::initialize();

		$this->model->setUser($this->getContainer()->get('app')->getUser());

		return $this;
	}
}
