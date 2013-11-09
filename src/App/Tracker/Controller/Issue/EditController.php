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
 * Controller class to edit an item via the tracker component.
 *
 * @since  1.0
 */
class EditController extends AbstractTrackerController
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

		$app->getUser()->authorize('edit');

		// Set the default view
		$input->set('view', 'issue');
		$input->set('layout', 'edit');
	}
}
