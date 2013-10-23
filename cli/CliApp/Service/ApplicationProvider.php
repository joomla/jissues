<?php
/**
 * Part of the Joomla Tracker CLI Service Package
 *
 * @copyright  Copyright (C) 2013 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CliApp\Service;

use Joomla\Application\AbstractApplication;
use Joomla\DI\ServiceProviderInterface;
use Joomla\DI\Container as JoomlaContainer;

/**
 * Class Application service.
 *
 * @since  1.0
 */
class ApplicationProvider implements ServiceProviderInterface
{
	/**
	 * Constructor
	 *
	 * @param   AbstractApplication  $app  Application instance
	 *
	 * @since   1.0
	 */
	public function __construct(AbstractApplication $app)
	{
		$this->app = $app;
	}

	/**
	 * Registers the service provider with a DI container.
	 *
	 * @param   JoomlaContainer  $container  The DI container.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	public function register(JoomlaContainer $container)
	{
		$app = $this->app;

		$container->set('app',
			function () use ($app)
			{
				return $app;
			}, true, true
		);
	}
}
