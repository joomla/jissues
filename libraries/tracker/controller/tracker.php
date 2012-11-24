<?php
/**
 * @package     JTracker
 * @subpackage  Controller
 *
 * @copyright   Copyright (C) 2012 Open Source Matters. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

/**
 * Generic Joomla! Issue Tracker Controller class
 *
 * @package     JTracker
 * @subpackage  Controller
 * @since       1.0
 */
abstract class JControllerTracker extends JControllerBase
{
	/**
	 * The default list view for the component
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $default_list_view;

	/**
	 * The URL option for the component.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $option;

	/**
	 * Constructor
	 *
	 * @since   1.0
	 * @throws  Exception
	 */
	public function __construct()
	{
		parent::__construct();

		// Get the option from the input object
		if (empty($this->option))
		{
			$this->option = $this->input->getCmd('option');
		}
	}

	/**
	 * Method to check if you can add a new record.
	 *
	 * @param   array  $data  An array of input data.
	 *
	 * @return  boolean
	 *
	 * @since   12.2
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
			$app    = JFactory::getApplication();
			$values = (array) $app->getUserState($context . '.id');

			$result = in_array((int) $id, $values);

			if (defined('JDEBUG') && JDEBUG)
			{
				JLog::add(
					sprintf(
						'Checking edit ID %s.%s: %d %s',
						$context,
						$id,
						(int) $result,
						str_replace("\n", ' ', print_r($values, 1))
					),
					JLog::INFO,
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
	 * @throws  RuntimeException
	 */
	public function execute()
	{
		// Get the application
		/* @var JApplicationTracker $app */
		$app = $this->getApplication();

		// Get the document object.
		$document = $app->getDocument();

		$vName   = $app->input->getWord('view', $this->default_list_view);
		$vFormat = $document->getType();
		$lName   = $app->input->getWord('layout', 'default');

		$app->input->set('view', $vName);

		// Register the layout paths for the view
		$paths = new SplPriorityQueue;
		$paths->insert(JPATH_THEMES . '/' . $app->getTemplate() . '/html/' . $this->option . '/' . $vName, 'normal');
		$paths->insert(JPATH_COMPONENT . '/view/' . $vName . '/tmpl', 'normal');

		$base   = ucfirst(substr($this->option, 4));
		$vClass = $base . 'View' . ucfirst($vName) . ucfirst($vFormat);
		$mClass = $base . 'Model' . ucfirst($vName);

		// If a model doesn't exist for our view, revert to the default model
		if (!class_exists($mClass))
		{
			$mClass = $base . 'ModelDefault';

			if (!class_exists($mClass))
			{
				throw new RuntimeException(sprintf('No model found for view %s or a default model for %s', $vName, $this->option));
			}
		}

		// Make sure the view class exists
		if (!class_exists($vClass))
		{
			throw new RuntimeException(sprintf('Class %s not found', $vClass));
		}

		/* @var JViewHtml $view */
		$view = new $vClass(new $mClass, $paths);
		$view->setLayout($lName);

		// Render our view.
		echo $view->render();

		return true;
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
		$tmpl   = $this->input->get('tmpl');
		$layout = $this->input->get('layout', 'edit');
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
	 * Gets the URL arguments to append to a list redirect.
	 *
	 * @return  string  The arguments to append to the redirect URL.
	 *
	 * @since   1.0
	 */
	protected function getRedirectToListAppend()
	{
		$tmpl = $this->input->get('tmpl');
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
				JLog::add(
					sprintf(
						'Holding edit ID %s.%s %s',
						$context,
						$id,
						str_replace("\n", ' ', print_r($values, 1))
					),
					JLog::INFO,
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
		$app    = JFactory::getApplication();
		$values = (array) $app->getUserState($context . '.id');

		// Do a strict search of the edit list values.
		$index = array_search((int) $id, $values, true);

		if (is_int($index))
		{
			unset($values[$index]);
			$app->setUserState($context . '.id', $values);

			if (defined('JDEBUG') && JDEBUG)
			{
				JLog::add(
					sprintf(
						'Releasing edit ID %s.%s %s',
						$context,
						$id,
						str_replace("\n", ' ', print_r($values, 1))
					),
					JLog::INFO,
					'controller'
				);
			}
		}
	}
}
