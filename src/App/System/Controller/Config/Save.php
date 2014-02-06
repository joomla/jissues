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
	 * @throws  \RuntimeException
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

		if (!file_put_contents(JPATH_ROOT . '/etc/config.json', json_encode($config, JSON_PRETTY_PRINT)))
		{
			throw new \RuntimeException('Could not write the configuration data to file /etc/config.json');
		}

		return '@todo..';
	}
}
