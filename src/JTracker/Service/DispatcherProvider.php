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
use Joomla\Event\Dispatcher;
use Joomla\Event\DispatcherInterface;

/**
 * Event dispatcher service provider
 *
 * @since  1.0
 */
class DispatcherProvider implements ServiceProviderInterface
{
	/**
	 * Registers the service provider with a DI container.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function register(Container $container)
	{
		$container->alias('dispatcher', DispatcherInterface::class)
			->alias(Dispatcher::class, DispatcherInterface::class)
			->share(
				DispatcherInterface::class,
				function () {
					return new Dispatcher;
				},
				true
			);
	}
}
