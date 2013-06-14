<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Groups\Controller;

use App\Tracker\Controller\DefaultController;
use Joomla\Application\AbstractApplication;
use Joomla\Input\Input;

/**
 * Controller class to add an item via the tracker component.
 *
 * @package  JTracker\Components\Tracker
 * @since    1.0
 */
class GroupController extends DefaultController
{
	/**
	 * Constructor
	 *
	 * @param   Input                $input  The input object.
	 * @param   AbstractApplication  $app    The application object.
	 *
	 * @since  1.0
	 */
	public function __construct(Input $input = null, AbstractApplication $app = null)
	{
		parent::__construct($input, $app);

		// Set the default view
		$this->getInput()->set('view', 'group');
		$this->getInput()->set('layout', 'edit');
	}

	/**
	 * Execute the controller.
	 *
	 * @return  string  The rendered view.
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		$this->getApplication()->getUser()->authorize('manage');

		parent::execute();
	}
}
