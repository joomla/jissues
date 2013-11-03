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
 * Controller class for the Text component
 *
 * @since  1.0
 */
class Page extends AbstractTrackerController
{
	/**
	 * The item view for the component
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $defaultView = 'page';
}
