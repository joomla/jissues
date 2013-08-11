<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Tracker\Controller\Issue;

use Joomla\Application\AbstractApplication;
use Joomla\Input\Input;
use JTracker\Controller\AbstractTrackerController;

/**
 * Controller class to add an item via the tracker component.
 *
 * @since  1.0
 */
class AddController extends AbstractTrackerController
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

		$this->getApplication()->getUser()->authorize('create');

		// Set the default view
		$this->getInput()->set('view', 'issue');
		$this->getInput()->set('layout', 'add');
	}
}
