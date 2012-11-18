<?php
/**
 * @package     JTracker
 * @subpackage  Model
 *
 * @copyright   Copyright (C) 2012 Open Source Matters. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

/**
 * Abstract base model for the tracker application
 *
 * @package     JTracker
 * @subpackage  Model
 * @since       1.0
 */
abstract class JModelTracker extends JModelDatabase
{
	/**
	 * The model (base) name
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $name = null;

	/**
	 * The URL option for the component.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $option = null;

	/**
	 * JTable instance
	 *
	 * @var    JTable
	 * @since  1.0
	 */
	protected $table;

	/**
	 * Constructor
	 *
	 * @since   1.0
	 * @throws  Exception
	 */
	public function __construct()
	{
		parent::__construct();

		// Guess the option from the class name (Option)Model(View).
		if (empty($this->option))
		{
			$classname = get_class($this);
			$modelpos = strpos($classname, 'Model');

			if ($modelpos === false)
			{
				throw new RuntimeException(JText::_('Model name does not follow standard.'), 500);
			}

			$this->option = 'com_' . strtolower(substr($classname, 0, $modelpos));
		}

		// Set the view name
		if (empty($this->name))
		{
			$this->getName();
		}
	}

	/**
	 * Method to get the model name
	 *
	 * The model name. By default parsed using the classname or it can be set
	 * by passing a $config['name'] in the class constructor
	 *
	 * @return  string  The name of the model
	 *
	 * @since   1.0
	 * @throws  Exception
	 */
	public function getName()
	{
		if (empty($this->name))
		{
			$classname = get_class($this);
			$modelpos = strpos($classname, 'Model');

			if ($modelpos === false)
			{
				throw new Exception(JText::_('JLIB_APPLICATION_ERROR_MODEL_GET_NAME'), 500);
			}

			$this->name = strtolower(substr($classname, $modelpos + 5));
		}

		return $this->name;
	}

	/**
	 * Method to get a table object, load it if necessary.
	 *
	 * @param   string  $name     The table name. Optional.
	 * @param   string  $prefix   The class prefix. Optional.
	 *
	 * @return  JTable  A JTable object
	 *
	 * @since   1.0
	 * @throws  RuntimeException
	 */
	public function getTable($name = '', $prefix = 'JTable')
	{
		if (empty($name))
		{
			$name = $this->getName();
		}

		$class = $prefix . ucfirst($name);

		if (!class_exists($class) && !($class instanceof JTable))
		{
			throw new RuntimeException(sprintf('Table class %s not found or is not an instance of JTable', $class));
		}

		$this->table = new $class($this->db);

		return $this->table;
	}
}
