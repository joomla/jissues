<?php
/**
 * Part of the Joomla Tracker Service Package
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Service;

use Application\Application;

use Joomla\Application\AbstractCliApplication;
use Joomla\Application\Cli\CliOutput;
use Joomla\Application\Cli\ColorStyle;
use Joomla\Application\Cli\Output\Processor\ColorProcessor;
use Joomla\Application\Cli\Output\Stdout;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\Input\Cli as BaseCli;
use Joomla\Input\Input;
use Joomla\Registry\Registry;

use JTracker\Input\Cli;

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
		$container->alias(AbstractCliApplication::class, Application::class)
			->share(
				Application::class,
				function (Container $container)
				{
					$application = new Application(
						$container->get(Cli::class),
						$container->get('config'),
						$container->get(CliOutput::class)
					);

					// Inject extra services
					$application->setContainer($container);
					$application->setDispatcher($container->get(DispatcherInterface::class));

					return $application;
				},
				true
			);

		$container->alias(BaseCli::class, Cli::class)
			->share(
				Cli::class,
				function ()
				{
					return new Cli;
				},
				true
			);

		$container->share(
			ColorProcessor::class,
			function (Container $container)
			{
				$processor = new ColorProcessor;

				/** @var Input $input */
				$input = $container->get(Cli::class);

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
			},
			true
		);

		$container->alias(CliOutput::class, Stdout::class)
			->share(
				Stdout::class,
				function (Container $container)
				{
					return new Stdout($container->get(ColorProcessor::class));
				},
				true
			);
	}
}
