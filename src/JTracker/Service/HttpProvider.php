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
use Joomla\Http\Http;
use Joomla\Http\HttpFactory;
use Psr\Http\Client\ClientInterface;

/**
 * HTTP service provider
 *
 * @since  1.0
 */
class HttpProvider implements ServiceProviderInterface
{
	/**
	 * Registers the service provider with a DI container.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	public function register(Container $container)
	{
		$container->alias('http', Http::class)
			->alias(ClientInterface::class, Http::class)
			->share(
				Http::class,
				function (Container $container)
				{
					/** @var HttpFactory $factory */
					$factory = $container->get('http.factory');

					return $factory->getHttp();
				},
				true
			);

		$container->alias('http.factory', HttpFactory::class)
			->share(
				HttpFactory::class,
				function (Container $container)
				{
					return new HttpFactory;
				},
				true
			);
	}
}
