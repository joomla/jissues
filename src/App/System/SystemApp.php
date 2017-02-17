<?php
/**
 * Part of the Joomla Tracker's System Application
 *
 * @copyright  Copyright (C) 2012-2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\System;

use Joomla\DI\Container;
use JTracker\AppInterface;

/**
 * System app
 *
 * @since  1.0
 */
class SystemApp implements AppInterface
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
			throw new \RuntimeException('Invalid router file for the System app: ' . __DIR__ . '/routes.json', 500);
		}

		/** @var \JTracker\Router\TrackerRouter $router */
		$router = $container->get('router');
		$router->addMaps($maps);
	}
}
