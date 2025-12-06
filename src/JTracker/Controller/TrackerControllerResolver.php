<?php

/**
 * Part of the Joomla Tracker Controller Package
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Controller;

use Joomla\Application\Controller\ContainerControllerResolver;
use Joomla\Router\ResolvedRoute;

/**
 * Controller resolver which supports creating controllers implementing the tracker's controller interface
 *
 * @since  1.0
 */
class TrackerControllerResolver extends ContainerControllerResolver
{
    /**
     * Resolve the controller for a route
     *
     * @param   ResolvedRoute  $route  The route to resolve the controller for
     *
     * @return  callable
     *
     * @since   1.0
     * @throws  \InvalidArgumentException
     */
    public function resolve(ResolvedRoute $route): callable
    {
        $controller = $route->getController();

        // Try to resolve a class name if it implements the application's interface
        if (\is_string($controller) && class_exists($controller) && \in_array(TrackerControllerInterface::class, class_implements($controller))) {
            try {
                return [$this->instantiateController($controller), 'execute'];
            } catch (\ArgumentCountError $error) {
                throw new \InvalidArgumentException(
                    \sprintf(
                        'Controller `%s` has required constructor arguments, cannot instantiate the class',
                        $controller
                    ),
                    0,
                    $error
                );
            }
        }

        return parent::resolve($route);
    }
}
