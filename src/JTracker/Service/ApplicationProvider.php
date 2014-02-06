<?php
/**
 * Part of the Joomla Tracker Service Package
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Service;

use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

use JTracker\Application;

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
	 * @param   Container  $container  The DI container.
	 *
	 * @return  Container  Returns itself to support chaining.
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	public function register(Container $container)
	{
		$container->set('JTracker\\Application',
			function ()
			{
				return $this->app;
			}, true, true
		);

		// Alias the application
		$container->alias('app', 'JTracker\\Application');
	}
}
