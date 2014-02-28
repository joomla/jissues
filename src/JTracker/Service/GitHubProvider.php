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
use Joomla\Github\Http;
use Joomla\Http\HttpFactory;
use Joomla\Http\Transport\Curl;
use Joomla\Registry\Registry;

use JTracker\Github\Github;

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
		$container->share('JTracker\\Github\\Github',
			function () use ($container)
			{
				$options = new Registry;

				$app = $container->get('app');

				// Check if we're in the web application and a token exists
				if ($app instanceof \JTracker\Application)
				{
					$session = $app->getSession();

					$token = $session->get('gh_oauth_access_token');
				}
				else
				{
					$token = false;
				}

				// If a token is active in the session (web app), use that for authentication (typically for a logged in user)
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

						// Store the other accounts
						$options->set('api.accounts', $accounts);
					}
					else
					{
						// Support for a single account
						$user     = $app->get('github.username');
						$password = $app->get('github.password');
					}

					// Add the username and password to the options object if both are set
					if ($user && $password)
					{
						// Set the options from the first account
						$options->set('api.username', $user);
						$options->set('api.password', $password);
					}
				}

				// The cURL extension is required to properly work.
				$transport = HttpFactory::getAvailableDriver($options, array('curl'));

				// Check if we *really* got a cURL transport...
				if (!($transport instanceof Curl))
				{
					throw new \RuntimeException('Please enable cURL.');
				}

				$http = new Http($options, $transport);

				// Instantiate Github
				return new GitHub($options, $http);
			}, true
		);

		// Alias the object
		$container->alias('gitHub', 'JTracker\\Github\\Github');
	}
}
