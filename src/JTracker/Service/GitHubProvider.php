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
use Joomla\Github\Github as JoomlaGitHub;
use Joomla\Github\Http as JoomlaGitHubHttp;
use Joomla\Http\HttpFactory;
use Joomla\Registry\Registry;

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
	 * @return  Container  Returns the container to support chaining.
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	public function register(Container $container)
	{
		return $container->set('Joomla\\Github\\Github',
			function () use ($container)
			{
				$options = new Registry;

				/* @var \JTracker\Application $app */
				$app     = $container->get('app');
				$session = $app->getSession();

				$token = $session->get('gh_oauth_access_token');

				// If a token is active in the session, use that for authentication (typically for a logged in user)
				if ($token)
				{
					$options->set('gh.token', $token);
				}
				// Otherwise fall back to an account from the system configuration
				else
				{
					// Check for support for multiple accounts
					$accounts = $app->get('github.accounts');

					if ($accounts)
					{
						$user     = isset($accounts[0]->username) ? $accounts[0]->username : null;
						$password = isset($accounts[0]->password) ? $accounts[0]->password : null;

						if ($user && $password)
						{
							// Set the options from the first account
							$options->set('api.username', $user);
							$options->set('api.password', $password);
						}

						// Store the other accounts
						$options->set('api.accounts', $accounts);
					}
					else
					{
						// Support for a single account
						$user     = $app->get('github.username');
						$password = $app->get('github.password');

						if ($user && $password)
						{
							// Set the options
							$options->set('api.username', $user);
							$options->set('api.password', $password);
						}
					}
				}

				// GitHub API works best with cURL
				$transport = HttpFactory::getAvailableDriver($options, array('curl'));

				$http = new JoomlaGitHubHttp($options, $transport);

				// Instantiate Github
				return new JoomlaGitHub($options, $http);
			}
		)->alias('gitHub', 'Joomla\\Github\\Github');
	}
}
