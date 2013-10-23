<?php
/**
 * Part of the Joomla Tracker Service Package
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JTracker\Service;

use Joomla\DI\Container as JoomlaContainer;
use Joomla\DI\ServiceProviderInterface;

use JTracker\Application;
use JTracker\Container;

/**
 * Application service provider
 *
 * @since  1.0
 */
class ApplicationProvider implements ServiceProviderInterface
{
	/**
	 * Application instance
	 *
	 * @var    Application
	 * @since  1.0
	 */
	private $app;

	/**
	 * Constructor
	 *
	 * @param   Application  $app  Application instance
	 *
	 * @since   1.0
	 */
	public function __construct(Application $app)
	{
		$this->app = $app;
	}

	/**
	 * Registers the service provider with a DI container.
	 *
	 * @param   JoomlaContainer  $container  The DI container.
	 *
	 * @return  Container  Returns itself to support chaining.
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	public function register(JoomlaContainer $container)
	{
		$app = $this->app;

		$container->set('JTracker\\Application',
			function () use ($app)
			{
				return $app;
			}, true, true
		);

		// Alias the application
		$container->alias('app', 'JTracker\\Application');
	}
}
