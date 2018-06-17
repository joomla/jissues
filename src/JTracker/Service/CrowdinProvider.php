<?php
/**
 * Part of the Joomla Tracker Service Package
 *
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Service;

use ElKuKu\Crowdin\Crowdin;

use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Registry\Registry;

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
		$container->alias('crowdin', Crowdin::class)
			->share(
				Crowdin::class,
				function (Container $container)
				{
					/** @var Registry $config */
					$config = $container->get('config');

					// Instantiate Crowdin
					return new Crowdin($config->get('crowdin.project'), $config->get('crowdin.api-key'));
				},
				true
			);
	}
}
