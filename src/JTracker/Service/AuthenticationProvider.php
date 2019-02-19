<?php
/**
 * Part of the Joomla Tracker Service Package
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Service;

use Joomla\Authentication\Authentication;
use Joomla\Authentication\AuthenticationStrategyInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Input\Input;
use JTracker\Authentication\GitHub\GitHubLoginHelper;
use JTracker\Authentication\Strategy\GitHubAuthenticationStrategy;

/**
 * Authentication service provider
 *
 * @since  1.0
 */
class AuthenticationProvider implements ServiceProviderInterface
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
		$container->alias('authentication', Authentication::class)
			->share(
				Authentication::class,
				function (Container $container)
				{
					$authentication = new Authentication;
					$authentication->addStrategy('github', $container->get(GitHubAuthenticationStrategy::class));

					return $authentication;
				},
				true
			);

		$container->share(
			GitHubLoginHelper::class,
			function (Container $container)
			{
				return new GitHubLoginHelper($container);
			},
			true
		);

		$container->share(
			GitHubAuthenticationStrategy::class,
			function (Container $container)
			{
				return new GitHubAuthenticationStrategy($container->get(GitHubLoginHelper::class), $container->get(Input::class));
			},
			true
		);
	}
}
