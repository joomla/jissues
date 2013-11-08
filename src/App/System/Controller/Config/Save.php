<?php
/**
 * Part of the Joomla Tracker's Support Application
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\System\Controller\Config;

use JTracker\Controller\AbstractTrackerController;

/**
 * Controller class to save the configuration
 *
 * @since  1.0
 */
class Save extends AbstractTrackerController
{
	/**
	 * Execute the controller.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 * @throws  \UnexpectedValueException
	 */
	public function execute()
	{
		$this->getApplication()->getUser()->authorize('admin');

		$config = $this->getApplication()->input->get('config', array(), 'array');

		if (!$config)
		{
			throw new \UnexpectedValueException('No config to save...');
		}

		echo '<h1>SaveConfig</h1>';
		echo '<h2>Please Copy&amp;Paste + Save =;)</h2>@todo saveMe...';

		echo '<pre style="background-color: #ffc; color: darkred; padding: 1em; border: 3px solid lime;">';

		echo json_encode($config, JSON_PRETTY_PRINT);

		// @todo write a small JSON prettyPrint function for PHP < 5.4

		echo '</pre>';
	}
}
