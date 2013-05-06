<?php
/**
 * @package     JTracker\Controller
 *
 * @copyright   Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tracker\Controller;

use Joomla\Application\AbstractApplication;
use Joomla\Controller\AbstractController;
use Joomla\Input\Input;
use Joomla\Log\Log;
use Joomla\View\AbstractHtmlView;

/**
 * Generic Joomla! Issue Tracker Controller class
 *
 * @package  JTracker\Controller
 * @since    1.0
 */
abstract class AbstractTrackerController extends AbstractController
{
	/**
	 * The default view for the component
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $defaultView;

	/**
	 * The component being executed.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $component;

	protected $view;

	/**
	 * Constructor
	 *
	 * @param   Input                $input  The input object.
	 * @param   AbstractApplication  $app    The application object.
	 *
	 * @since  1.0
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
			$className = str_replace('Joomla\\Tracker\\Components\\', '', $fqcn);

			// Explode the remaining name into an array
			$classArray = explode('\\', $className);

			// Set the component as the first object in this array
			$this->component = $classArray[0];
		}

		$this->setup();
	}

	/**
	 * Method to check if you can add a new record.
	 *
	 * @param   array  $data  An array of input data.
	 *
	 * @return  boolean
	 *
	 * @since   1.0
	 */
	protected function allowAdd($data = array())
	{
		return JFactory::getUser()->authorise('core.create', $this->option);
	}

	/**
	 * Method to check if you can edit an existing record.
	 *
	 * @param   string  $option  The component to check the permissions of
	 * @param   array   $data    An array of input data.
	 * @param   string  $key     The name of the key for the primary key; default is id.
	 *
	 * @return  boolean
	 *
	 * @since   1.0
	 */
	protected function allowEdit($option, $data = array(), $key = 'id')
	{
		return JFactory::getUser()->authorise('core.edit', $option);
	}

	/**
	 * Method to check if you can save a new or existing record.
	 *
	 * @param   array   $data  An array of input data.
	 * @param   string  $key   The name of the key for the primary key.
	 *
	 * @return  boolean
	 *
	 * @since   1.0
	 */
	protected function allowSave($data, $key = 'id')
	{
		$recordId = isset($data[$key]) ? $data[$key] : '0';

		if ($recordId)
		{
			return $this->allowEdit($data, $key);
		}
		else
		{
			return $this->allowAdd($data);
		}
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
	 * @return  string  The rendered view.
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
		$lName   = $input->getWord('layout', 'default');

		$input->set('view', $vName);

		// Register the layout paths for the view
		$paths = new \SplPriorityQueue;
		$paths->insert(JPATH_COMPONENT . '/View/' . ucfirst($vName) . '/tmpl', 'normal');

		$base   = '\\Joomla\\Tracker\\Components\\' . $this->component;
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
			$vClass = $base . '\\View\\DefaultView';

			// If there still isn't a class, panic.
			if (!class_exists($vClass))
			{
				throw new \RuntimeException(sprintf('Class %s not found', $vClass));
			}
		}

		/* @var AbstractHtmlView $view */
		$view = new $vClass(new $mClass, $paths);
		$view->setLayout($lName);

		// Render our view.
		echo $view->render();

		return true;
	}

	protected function setup()
	{

	}

	/**
	 * Gets the URL arguments to append to an item redirect.
	 *
	 * @param   integer  $recordId  The primary key id for the item.
	 * @param   string   $urlVar    The name of the URL variable for the id.
	 *
	 * @return  string  The arguments to append to the redirect URL.
	 *
	 * @since   1.0
	 */
	protected function getRedirectToItemAppend($recordId = null, $urlVar = 'id')
	{
		$input  = $this->getInput();
		$tmpl   = $input->get('tmpl');
		$layout = $input->get('layout', 'edit');
		$append = '';

		// Setup redirect info.
		if ($tmpl)
		{
			$append .= '&tmpl=' . $tmpl;
		}

		if ($layout)
		{
			$append .= '&layout=' . $layout;
		}

		if ($recordId)
		{
			$append .= '&' . $urlVar . '=' . $recordId;
		}

		return $append;
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
	 * Gets the URL arguments to append to a list redirect.
	 *
	 * @return  string  The arguments to append to the redirect URL.
	 *
	 * @since   1.0
	 */
	protected function getRedirectToListAppend()
	{
		$tmpl = $this->getInput()->get('tmpl');
		$append = '';

		// Setup redirect info.
		if ($tmpl)
		{
			$append .= '&tmpl=' . $tmpl;
		}

		return $append;
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
