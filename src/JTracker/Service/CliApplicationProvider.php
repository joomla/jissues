<?php
/**
 * Part of the Joomla Tracker Service Package
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Service;

use Application\Application;
use Joomla\Application\Cli\ColorStyle;
use Joomla\Application\Cli\Output\Processor\ColorProcessor;
use Joomla\Application\Cli\Output\Stdout;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Input\Cli;
use Joomla\Input\Input;
use Joomla\Registry\Registry;

/**
 * CLI application service provider
 *
 * @since  1.0
 */
class CliApplicationProvider implements ServiceProviderInterface
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
		$container->alias('Joomla\\Application\\AbstractCliApplication', 'Application\\Application')
			->share(
				'Application\\Application',
				function (Container $container)
				{
					$application = new Application(
						$container->get('Joomla\\Input\\Cli'),
						$container->get('config'),
						$container->get('Joomla\\Application\\Cli\\CliOutput')
					);

					// Inject extra services
					$application->setContainer($container);
					$application->setDispatcher($container->get('dispatcher'));

					return $application;
				}
			);

		$container->share(
			'Joomla\\Input\\Cli',
			function ()
			{
				return new Cli;
			}
		);

		$container->share(
			'Joomla\\Application\\Cli\\Output\\Processor\\ColorProcessor',
			function (Container $container)
			{
				$processor = new ColorProcessor;

				/** @var Input $input */
				$input = $container->get('Joomla\\Input\\Cli');

				/** @var Registry $config */
				$config = $container->get('config');

				if ($input->get('nocolors') || !$config->get('cli-application.colors'))
				{
					$processor->noColors = true;
				}

				// Setup app colors (also required in "nocolors" mode - to strip them).
				$processor->addStyle('b', new ColorStyle('', '', ['bold']))
					->addStyle('title', new ColorStyle('yellow', '', ['bold']))
					->addStyle('ok', new ColorStyle('green', '', ['bold']));

				return $processor;
			}
		);

		$container->alias('Joomla\\Application\\Cli\\CliOutput', 'Joomla\\Application\\Cli\\Output\\Stdout')
			->share(
				'Joomla\\Application\\Cli\\Output\\Stdout',
				function (Container $container)
				{
					return new Stdout($container->get('Joomla\\Application\\Cli\\Output\\Processor\\ColorProcessor'));
				}
			);
	}
}
