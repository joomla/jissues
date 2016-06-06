<?php
/**
 * Part of the Joomla Tracker Service Package
 *
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Service;

use JTracker\Crowdin\Client;

use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

/**
 * Crowdin service provider
 *
 * @since  1.0
 */
class CrowdinProvider implements ServiceProviderInterface
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
		$container->set('JTracker\\Crowdin\\Client',
			function () use ($container)
			{
				/* @var \JTracker\Application $app */
				$app = $container->get('app');

				// Instantiate Crowdin
				return new Client($app->get('crowdin.project'), $app->get('crowdin.api-key'));
			}
		)->alias('crowdin', 'JTracker\\Crowdin\\Client');
	}
}
