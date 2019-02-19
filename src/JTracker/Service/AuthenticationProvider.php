<?php
/**
 * Part of the Joomla Tracker Service Package
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Service;

use Joomla\Application\AbstractWebApplication;
use Joomla\Authentication\Authentication;
use Joomla\Database\DatabaseDriver;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Github\Github;
use Joomla\Http\Http;
use Joomla\Input\Input;
use Joomla\Registry\Registry;
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
				/** @var Registry $config */
				$config = $container->get('config');

				// Single account
				$clientId     = $config->get('github.client_id');
				$clientSecret = $config->get('github.client_secret');

				// Multiple accounts
				if (!$clientId)
				{
					$githubAccounts = $config->get('github.accounts');

					// Use credentials from the first account
					$clientId     = $githubAccounts[0]->client_id ?? '';
					$clientSecret = $githubAccounts[0]->client_secret ?? '';
				}

				return new GitHubLoginHelper(
					$container->get(AbstractWebApplication::class),
					$container->get(DatabaseDriver::class),
					$container->get(Github::class),
					$container->get(Http::class),
					$clientId,
					$clientSecret,
					$config->get('github.auth_scope', 'public_repo,read:user')
				);
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
