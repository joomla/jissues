<?php
/**
 * Part of the Joomla Tracker CLI Service Package
 *
 * @copyright  Copyright (C) 2013 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CliApp\Service\Provider;

use CliApp\Application\CliApplication;
use Joomla\DI\ServiceProviderInterface;

use JTracker\Container;

/**
 * Class Application service.
 *
 * @since  1.0
 */
class Application implements ServiceProviderInterface
{
	/**
	 * Application instance
	 *
	 * @var    CliApplication
	 * @since  1.0
	 */
	private static $app;

	/**
	 * Constructor
	 *
	 * @param   CliApplication  $app  Application instance
	 *
	 * @since   1.0
	 */
	public function __construct(CliApplication $app)
	{
		static::$app = $app;
	}

	/**
	 * Registers the service provider with a DI container.
	 *
	 * @param   \Joomla\DI\Container  $container  The DI container.
	 *
	 * @return  Container  Returns itself to support chaining.
	 *
	 * @since   1.0
	 */
	public function register(\Joomla\DI\Container $container)
	{
		$app = static::$app;

		$container->set(
			'JTracker\\Application\\TrackerApplication',
			function () use ($app)
			{
				return $app;
			}, true, true
		);

		// Alias the application
		$container->alias('app', 'JTracker\\Application\\TrackerApplication');
	}
}
