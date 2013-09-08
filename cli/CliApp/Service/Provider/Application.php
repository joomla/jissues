<?php
/**
 * Part of the Joomla Tracker CLI Service Package
 *
 * @copyright  Copyright (C) 2013 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CliApp\Service\Provider;

use Joomla\Application\AbstractApplication;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

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
	 * @var    AbstractApplication
	 * @since  1.0
	 */
	private static $app;

	/**
	 * Constructor
	 *
	 * @param   AbstractApplication  $app  Application instance
	 *
	 * @since   1.0
	 */
	public function __construct(AbstractApplication $app)
	{
		static::$app = $app;
	}

	/**
	 * Registers the service provider with a DI container.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	public function register(Container $container)
	{
		$app = static::$app;

		$container->set(
			'app',
			function () use ($app)
			{
				return $app;
			}, true, true
		);
	}
}
