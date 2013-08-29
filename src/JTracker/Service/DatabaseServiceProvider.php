<?php
/**
 * Part of the Joomla Tracker Service Package
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JTracker\Service;

use Joomla\Database\DatabaseDriver;
use Joomla\DI\Container as JoomlaContainer;
use Joomla\DI\ServiceProviderInterface;
use JTracker\Container;

class DatabaseServiceProvider implements ServiceProviderInterface
{
	/**
	 * Registers the service provider with a DI container.
	 *
	 * @param   Container $container  The DI container.
	 *
	 * @return  Container  Returns itself to support chaining.
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	public function register(JoomlaContainer $container)
	{
		$container->set('Joomla\\Database\\DatabaseDriver', function ()
			{
				static $db;

				if (is_null($db))
				{
					$app = Container::retrieve('app');

					$options = array(
						'driver' => $app->get('database.driver'),
						'host' => $app->get('database.host'),
						'user' => $app->get('database.user'),
						'password' => $app->get('database.password'),
						'database' => $app->get('database.name'),
						'prefix' => $app->get('database.prefix')
					);

					$db = DatabaseDriver::getInstance($options);
					$db->setDebug($app->get('debug.database', false));
				}

				return $db;
			}, true, true
		);

		// Alias the database
		$container->alias('db', 'Joomla\\Database\\DatabaseDriver');
	}
}
