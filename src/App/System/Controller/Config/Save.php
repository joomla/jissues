<?php
/**
 * Part of the Joomla Tracker's Support Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
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
	 * @return  string
	 *
	 * @since   1.0
	 * @throws  \UnexpectedValueException
	 */
	public function execute()
	{
		$this->container->get('app')->getUser()->authorize('admin');

		$config = $this->container->get('app')->input->get('config', array(), 'array');

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

		return '@todo..';
	}
}
