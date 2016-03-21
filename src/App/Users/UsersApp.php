<?php
/**
 * Part of the Joomla Tracker's Users Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Users;

use Joomla\DI\Container;
use JTracker\AppInterface;

/**
 * Users app
 *
 * @since  1.0
 */
class UsersApp implements AppInterface
{
	/**
	 * Loads services for the component into the application's DI Container
	 *
	 * @param   Container  $container  DI Container to load services into
	 *
	 * @return  void
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	public function loadServices(Container $container)
	{
		// Register the component routes
		$maps = json_decode(file_get_contents(__DIR__ . '/routes.json'), true);

		if (!$maps)
		{
			throw new \RuntimeException('Invalid router file for the Users app: ' . __DIR__ . '/routes.json', 500);
		}

		/** @var \JTracker\Application $application */
		$application = $container->get('app');
		$application->getRouter()->addMaps($maps);
	}
}
