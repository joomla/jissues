<?php
/**
 * @package    JTracker\Components\Users
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tracker\Components\Users\Controller;

use Joomla\Application\AbstractApplication;
use Joomla\Input\Input;
use Joomla\Tracker\Controller\AbstractTrackerController;

/**
 * Default controller class for the Users component.
 *
 * @package  JTracker\Components\Users
 * @since    1.0
 */
class DefaultController extends AbstractTrackerController
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

		// Set the default views
		$this->default_list_view = 'users';
	}
}
