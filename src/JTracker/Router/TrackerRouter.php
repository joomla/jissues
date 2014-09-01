<?php
/**
 * Part of the Joomla Tracker Router Package
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Router;

use Joomla\DI\Container;
use Joomla\DI\ContainerAwareInterface;
use Joomla\Input\Input;
use Joomla\Router\Router;

use JTracker\Controller\AbstractTrackerController;
use JTracker\Router\Exception\RoutingException;

/**
 * Joomla! Tracker Router
 *
 * @since  1.0
 */
class TrackerRouter extends Router
{
	/**
	 * Container object to inject into controllers
	 *
	 * @var    Container
	 * @since  1.0
	 */
	protected $container;

	/**
	 * Constructor.
	 *
	 * @param   Container  $container  The DI container.
	 * @param   Input      $input      The Input object.
	 *
	 * @since   1.0
	 */
	public function __construct(Container $container, Input $input = null)
	{
		parent::__construct($input);

		$this->container = $container;
	}

	/**
	 * Find and execute the appropriate controller based on a given route.
	 *
	 * @param   string  $route  The route string for which to find and execute a controller.
	 *
	 * @return  AbstractTrackerController
	 *
	 * @since   1.0
	 * @throws  RoutingException
	 */
	public function getController($route)
	{
		try
		{
			return parent::getController($route);
		}
		catch (\InvalidArgumentException $e)
		{
			// 404
			throw new RoutingException($e->getMessage());
		}
		catch (\RuntimeException $e)
		{
			// 404
			throw new RoutingException($e->getMessage());
		}
	}

	/**
	 * Get a AbstractTrackerController object for a given name.
	 *
	 * @param   string  $name  The controller name (excluding prefix) for which to fetch and instance.
	 *
	 * @return  AbstractTrackerController
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	protected function fetchController($name)
	{
		// Derive the controller class name.
		$class = $this->controllerPrefix . ucfirst($name);

		// Check for the requested controller.
		if (!class_exists($class))
		{
			throw new \RuntimeException(sprintf('Controller %s not found', $name));
		}

		// Instantiate the controller.
		$controller = new $class($this->input, $this->container->get('app'));

		if ($controller instanceof ContainerAwareInterface)
		{
			$controller->setContainer($this->container);
		}

		return $controller;
	}
}
