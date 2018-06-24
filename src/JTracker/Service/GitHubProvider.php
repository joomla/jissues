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
use Joomla\Github\Github as BaseGithub;
use JTracker\GitHub\Github;
use JTracker\Github\GithubFactory;

/**
 * GitHub service provider
 *
 * @since  1.0
 */
class GitHubProvider implements ServiceProviderInterface
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
		$container->alias('gitHub', Github::class)
			->alias(BaseGithub::class, Github::class)
			->share(
				Github::class,
				function (Container $container)
				{
					// Call the Github factory's getInstance method and inject the application; it handles the rest of the configuration
					return GithubFactory::getInstance($container->get('app'));
				},
				true
			);
	}
}
