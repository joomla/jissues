<?php
/**
 * Part of the Joomla Tracker Service Package
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Service;

use BabDev\Transifex\Transifex;

use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Registry\Registry;

/**
 * Transifex service provider
 *
 * @since  1.0
 */
class TransifexProvider implements ServiceProviderInterface
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
		return $container->share(
			'BabDev\\Transifex\\Transifex',
			function (Container $container)
			{
				$options = new Registry;

				/* @var \JTracker\Application $app */
				$app = $container->get('app');

				$options->set('api.username', $app->get('transifex.username'));
				$options->set('api.password', $app->get('transifex.password'));

				// Instantiate Transifex
				return new Transifex($options);
			},
			true
		)->alias('transifex', 'BabDev\\Transifex\\Transifex');
	}
}
