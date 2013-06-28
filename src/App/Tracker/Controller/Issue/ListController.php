<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Tracker\Controller\Issue;

use Joomla\Application\AbstractApplication;
use Joomla\Factory;
use Joomla\Input\Input;
use JTracker\Controller\AbstractTrackerController;

/**
 * Default controller class for the Tracker component.
 *
 * @since  1.0
 */
class ListController extends AbstractTrackerController
{
	/**
	 * Constructor
	 *
	 * @param   Input                $input  The input object.
	 * @param   AbstractApplication  $app    The application object.
	 *
	 * @since   1.0
	 */
	public function __construct(Input $input = null, AbstractApplication $app = null)
	{
		parent::__construct($input, $app);

		// Set the default view
		$this->defaultView = 'issues';
	}

	/**
	 * Execute the controller.
	 *
	 * @since   1.0
	 * @return  string  The rendered view.
	 */
	public function execute()
	{
		$this->getApplication()->getUser()->authorize('view');

		parent::execute();
	}
}
