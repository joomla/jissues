<?php
/**
 * Part of the Joomla Tracker Service Package
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CliApp\Service;

use Joomla\Github\Http;
use Joomla\Http\HttpFactory;
use Joomla\Http\Transport\Curl;
use Joomla\Registry\Registry;
use Joomla\DI\ServiceProviderInterface;
use Joomla\DI\Container;

use JTracker\Github\Github;

/**
 * Class GitHubProvider
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
	 * @return  Container  Returns itself to support chaining.
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
