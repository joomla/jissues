<?php
/**
 * Part of the Joomla Tracker Controller Package
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JTracker\Controller;

use Joomla\Application\AbstractApplication;
use Joomla\Controller\AbstractController;
use Joomla\Input\Input;
use Joomla\Log\Log;
use JTracker\Application;
use JTracker\View\AbstractTrackerHtmlView;

/**
 * Abstract Controller class for the Tracker Application
 *
 * @since  1.0
 */
abstract class AbstractTrackerController extends AbstractController
{
	/**
	 * The default view for the app
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $defaultView;

	/**
	 * The app being executed.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $component;

	/**
	 * Constructor.
	 *
	 * @param   Input                $input  The input object.
	 * @param   AbstractApplication  $app    The application object.
	 *
	 * @since   1.0
	 */
	public function __construct(Input $input = null, AbstractApplication $app = null)
	{
		parent::__construct($input, $app);

		// Get the option from the input object
		if (empty($this->component))
		{
			// Get the fully qualified class name for the current object
			$fqcn = (get_class($this));

			// Strip the base component namespace off
			$className = str_replace('App\\', '', $fqcn);

			// Explode the remaining name into an array
			$classArray = explode('\\', $className);

			// Set the component as the first object in this array
			$this->component = $classArray[0];
		}
	}

	/**
	 * Get the application object.
	 *
	 * NOTE: This method only exists to provide autocomplete for the child classes
	 *
	 * @return  Application
	 *
	 * @since   1.0
	 */
	public function getApplication()
	{
		return parent::getApplication();
	}

	/**
	 * Method to check whether an ID is in the edit list.
	 *
	 * @param   string   $context  The context for the session storage.
	 * @param   integer  $id       The ID of the record to add to the edit list.
	 *
	 * @return  boolean  True if the ID is in the edit list.
	 *
	 * @since   1.0
	 */
	protected function checkEditId($context, $id)
	{
		if ($id)
		{
			$app    = $this->getApplication();
			$values = (array) $app->getUserState($context . '.id');

			$result = in_array((int) $id, $values);

			if (defined('JDEBUG') && JDEBUG)
			{
				Log::add(
					sprintf(
						'Checking edit ID %s.%s: %d %s',
						$context,
						$id,
						(int) $result,
						str_replace("\n", ' ', print_r($values, 1))
					),
					Log::INFO,
					'controller'
				);
			}

			return $result;
		}
		else
		{
			// No id for a new item.
			return true;
		}
	}

	/**
	 * Execute the controller.
	 *
	 * This is a generic method to execute and render a view and is not suitable for tasks.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	public function execute()
	{
		// Get the input
		$input = $this->getInput();

		// Get some data from the request
		$vName   = $input->getWord('view', $this->defaultView);
		$vFormat = $input->getWord('format', 'html');
		$lName   = $input->getCmd('layout', 'index');

		$input->set('view', $vName);

		$base   = '\\App\\' . $this->component;

		$vClass = $base . '\\View\\' . ucfirst($vName) . '\\' . ucfirst($vName) . ucfirst($vFormat) . 'View';
		$mClass = $base . '\\Model\\' . ucfirst($vName) . 'Model';

		// If a model doesn't exist for our view, revert to the default model
		if (!class_exists($mClass))
		{
			$mClass = $base . '\\Model\\DefaultModel';

			// If there still isn't a class, panic.
			if (!class_exists($mClass))
			{
				throw new \RuntimeException(sprintf('No model found for view %s or a default model for %s', $vName, $this->component));
			}
		}

		// Make sure the view class exists, otherwise revert to the default
		if (!class_exists($vClass))
		{
			$vClass = '\\JTracker\\View\\TrackerDefaultView';

			// If there still isn't a class, panic.
			if (!class_exists($vClass))
			{
				throw new \RuntimeException(sprintf('Class %s not found', $vClass));
			}
		}

		// Register the templates paths for the view
		$paths = array();

		$sub = ('php' == $this->getApplication()->get('renderer.type')) ? '/php' : '';

		$path = JPATH_TEMPLATES . $sub . '/' . strtolower($this->component);

		if (is_dir($path))
		{
			$paths[] = $path;
		}

		/* @type AbstractTrackerHtmlView $view */
		$view = new $vClass(new $mClass, $paths);
		$view->setLayout($vName . '.' . $lName);

		try
		{
			// Render our view.
			echo $view->render();
		}
		catch (\Exception $e)
		{
			echo $this->getApplication()->getDebugger()->renderException($e);
		}

		return;
	}

	/**
	 * Returns the current component
	 *
	 * @return  string  The component being executed.
	 *
	 * @since   1.0
	 */
	public function getComponent()
	{
		return $this->component;
	}

	/**
	 * Method to add a record ID to the edit list.
	 *
	 * @param   string   $context  The context for the session storage.
	 * @param   integer  $id       The ID of the record to add to the edit list.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	protected function holdEditId($context, $id)
	{
		$app = $this->getApplication();
		$values = (array) $app->getUserState($context . '.id');

		// Add the id to the list if non-zero.
		if (!empty($id))
		{
			array_push($values, (int) $id);
			$values = array_unique($values);
			$app->setUserState($context . '.id', $values);

			if (defined('JDEBUG') && JDEBUG)
			{
				Log::add(
					sprintf(
						'Holding edit ID %s.%s %s',
						$context,
						$id,
						str_replace("\n", ' ', print_r($values, 1))
					),
					Log::INFO,
					'controller'
				);
			}
		}
	}

	/**
	 * Method to check whether an ID is in the edit list.
	 *
	 * @param   string   $context  The context for the session storage.
	 * @param   integer  $id       The ID of the record to add to the edit list.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	protected function releaseEditId($context, $id)
	{
		$app    = $this->getApplication();
		$values = (array) $app->getUserState($context . '.id');

		// Do a strict search of the edit list values.
		$index = array_search((int) $id, $values, true);

		if (is_int($index))
		{
			unset($values[$index]);
			$app->setUserState($context . '.id', $values);

			if (defined('JDEBUG') && JDEBUG)
			{
				Log::add(
					sprintf(
						'Releasing edit ID %s.%s %s',
						$context,
						$id,
						str_replace("\n", ' ', print_r($values, 1))
					),
					Log::INFO,
					'controller'
				);
			}
		}
	}
}
