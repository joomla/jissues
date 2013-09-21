<?php
/**
 * Part of the Joomla Tracker Service Package
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CliApp\Service;

use Joomla\DI\Container as JoomlaContainer;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Github\Github as JoomlaGitHub;
use Joomla\Registry\Registry;

use JTracker\Container;

/**
 * Class GitHubProvider
 *
 * @since  1.0
 */
class GitHubProvider implements ServiceProviderInterface
{
	/**
	 * Object instance
	 *
	 * @var    JoomlaGitHub
	 * @since  1.0
	 */
	private static $object;

	/**
	 * Registers the service provider with a DI container.
	 *
	 * @param   \Joomla\DI\Container  $container  The DI container.
	 *
	 * @throws \RuntimeException
	 * @return  Container  Returns itself to support chaining.
	 *
	 * @since   1.0
	 */
	public function register(JoomlaContainer $container)
	{
		if (is_null(static::$object))
		{
			$options = new Registry;

			$app = Container::retrieve('app');

			$user = $app->get('github.username');
			$password = $app->get('github.password');

			if ($user && $password)
			{
				// Set the options
				$options->set('api.username', $user);
				$options->set('api.password', $password);
			}

			// @todo temporary fix to avoid the "Socket" transport protocol
			$transport = \Joomla\Http\HttpFactory::getAvailableDriver($options, array('curl'));

			if (false == is_a($transport, 'Joomla\\Http\\Transport\\Curl'))
			{
				throw new \RuntimeException('Please enable cURL.');
			}

			$http = new \Joomla\Github\Http($options, $transport);

			// Instantiate Github
			static::$object = new JoomlaGitHub($options, $http);

			// @todo after fix this should be enough:
			// $this->github = new JoomlaGitHub($options);
		}

		$object = static::$object;

		$container->set('Joomla\\Github\\Github', function () use ($object)
		{
			return $object;
		}, true, true
		);

		// Alias the object
		$container->alias('gitHub', 'Joomla\\Github\\Github');
	}
}
