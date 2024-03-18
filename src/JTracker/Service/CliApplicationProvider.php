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
use Joomla\Event\DispatcherInterface;
use JTracker\Application\ConsoleApplication;

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
        $container->share(
            ConsoleApplication::class,
            function (Container $container) {
                $application = new ConsoleApplication(
                    null,
                    null,
                    $container->get('config'),
                );

                // Inject extra services
                $application->setContainer($container);
                $application->setDispatcher($container->get(DispatcherInterface::class));

                return $application;
            },
            true
        );
    }
}
