<?php
/**
 * @package     JTracker\Router
 *
 * @copyright   Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tracker\Router;

use Joomla\Application\AbstractApplication;
use Joomla\Controller\ControllerInterface;
use Joomla\Input\Input;
use Joomla\Router\Router;

/**
 * Joomla! Issue Tracker Router
 *
 * @package  JTracker\Router
 * @since    1.0
 */
class TrackerRouter extends Router
{
	/**
	 * Application object to inject into controllers
	 *
	 * @var    AbstractApplication
	 * @since  1.0
	 */
	protected $app;

	/**
	 * Constructor.
	 *
	 * @param   Input  $input              An optional input object from which to derive the route.  If none
	 *                                     is given than the input from the application object will be used.
	 * @param   AbstractApplication  $app  An optional application object to inject to controllers
	 *
	 * @since   1.0
	 */
	public function __construct(Input $input = null, AbstractApplication $app = null)
	{
		parent::__construct($input);

		$this->app = $app;
	}

	/**
	 * Get a ControllerInterface object for a given name.
	 *
	 * @param   string  $name  The controller name (excluding prefix) for which to fetch and instance.
	 *
	 * @return  ControllerInterface
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	protected function fetchController($name)
	{
		// Derive the controller class name.
		$class = $this->controllerPrefix . ucfirst($name);

		// Check for the requested controller.
		if (!class_exists($class) || !is_subclass_of($class, 'Joomla\\Controller\\ControllerInterface'))
		{
			// See if there's an action class in the libraries if we aren't calling the default task
			$task = $this->input->getCmd('task');
			if ($task && $task != 'default')
			{
				$class = '\\Joomla\\Tracker\\Controller\\' . ucfirst($task) . 'Controller';
			}

			if (!class_exists($class) || !is_subclass_of($class, 'Joomla\\Controller\\ControllerInterface'))
			{
				// Look for a default controller for the component
				// @TODO - Fix this up
				//$class = ucfirst($base) . 'DefaultController';

				if (!class_exists($class) || !is_subclass_of($class, 'Joomla\\Controller\\ControllerInterface'))
				{
					// Nothing found. Panic.
					throw new \RuntimeException(sprintf('Controller not found for %s task', $task));
				}
			}
		}

		// Instantiate the controller.
		$controller = new $class($this->input, $this->app);

		return $controller;
	}
}
