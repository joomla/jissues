<?php
/**
 * Part of the Joomla Tracker Service Package
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CliApp\Service;

use Joomla\Github\Http;
use Joomla\Github\Github;
use Joomla\Http\HttpFactory;
use Joomla\Registry\Registry;
use Joomla\Http\Transport\Curl;
use Joomla\DI\ServiceProviderInterface;
use Joomla\DI\Container as JoomlaContainer;

use JTracker\Container;

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
	 * @param   \Joomla\DI\Container  $container  The DI container.
	 *
	 * @throws  \RuntimeException
	 * @return  Container  Returns itself to support chaining.
	 *
	 * @since   1.0
	 */
	public function register(JoomlaContainer $container)
	{
		$container->share('Joomla\\Github\\Github',
			function () use ($container)
			{
				$options = new Registry;

				$app = $container->get('app');

				$user     = $app->get('github.username');
				$password = $app->get('github.password');

				if ($user && $password)
				{
					// Set the options
					$options->set('api.username', $user);
					$options->set('api.password', $password);
				}

				// @todo temporary fix to avoid the "Socket" transport protocol
				$transport = HttpFactory::getAvailableDriver($options, array('curl'));

				if (!($transport instanceof Curl))
				{
					throw new \RuntimeException('Please enable cURL.');
				}

				$http = new Http($options, $transport);

				// Instantiate Github
				return new GitHub($options, $http);

				// @todo after fix this should be enough:
				// return new GitHub($options);
			}, true
		);

		// Alias the object
		$container->alias('gitHub', 'Joomla\\Github\\Github');
	}
}
