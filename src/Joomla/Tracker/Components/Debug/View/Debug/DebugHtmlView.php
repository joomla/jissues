<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tracker\Components\Debug\View\Debug;

use Joomla\Factory;
use Joomla\Tracker\View\AbstractTrackerHtmlView;

/**
 * System configuration view.
 *
 * @since  1.0
 */
class DebugHtmlView extends AbstractTrackerHtmlView
{
	/**
	 * Method to render the view.
	 *
	 * @return  string  The rendered view.
	 *
	 * @since   1.0
	 */
	public function render()
	{
		/* @type \Joomla\Tracker\Application\TrackerApplication $application */
		// $application = Factory::$application;

		return parent::render();
	}
}
