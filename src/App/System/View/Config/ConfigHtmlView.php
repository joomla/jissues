<?php
/**
 * Part of the Joomla Tracker's Support Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\System\View\Config;

use JTracker\View\AbstractTrackerHtmlView;
use Joomla\Utilities\ArrayHelper;

/**
 * System configuration view.
 *
 * @since  1.0
 */
class ConfigHtmlView extends AbstractTrackerHtmlView
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
		$config = json_decode(file_get_contents(JPATH_CONFIGURATION . '/config.json'), true);

		$this->renderer->set('config', $config);

		return parent::render();
	}
}
