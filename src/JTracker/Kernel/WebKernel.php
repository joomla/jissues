<?php

/**
 * Part of the Joomla Tracker
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Kernel;

use Joomla\Application\AbstractApplication;
use Joomla\DI\Container;
use JTracker\Application\Application;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

/**
 * Web application kernel
 *
 * @since  1.0
 */
class WebKernel extends AbstractKernel
{
    /**
     * Build the service container
     *
     * @return  Container
     *
     * @since   1.0
     */
    protected function buildContainer(): Container
    {
        $container = parent::buildContainer();

        // Create the application aliases for the common 'app' key and base application class
        $container->alias(AbstractApplication::class, Application::class)
            ->alias('app', Application::class);

        // Create the logger aliases for the common 'monolog' key, the Monolog Logger class, and the PSR-3 interface
        $container->alias('monolog', 'monolog.logger.application')
            ->alias('logger', 'monolog.logger.application')
            ->alias(Logger::class, 'monolog.logger.application')
            ->alias(LoggerInterface::class, 'monolog.logger.application');

        // Set error reporting based on config
        $errorReporting = (int) $container->get('config')->get('system.error_reporting', 0);
        error_reporting($errorReporting);

        return $container;
    }
}
