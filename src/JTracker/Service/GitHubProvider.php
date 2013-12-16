<?php
/**
 * Part of the Joomla Tracker Service Package
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
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

				if ($token)
				{
					$options->set('gh.token', $token);
				}
				else
				{
					$options->set('api.username', $app->get('github.username'));
					$options->set('api.password', $app->get('github.password'));
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
