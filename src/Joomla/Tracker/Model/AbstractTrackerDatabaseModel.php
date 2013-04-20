<?php
/**
 * @package     JTracker\Model
 *
 * @copyright   Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tracker\Model;

use Joomla\Factory;
use Joomla\Model\AbstractDatabaseModel;

/**
 * Abstract base model for the tracker application
 *
 * @package  JTracker\Model
 * @since    1.0
 */
abstract class AbstractTrackerDatabaseModel extends AbstractDatabaseModel
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
	 */
	public function __construct()
	{
		parent::__construct(Factory::getDbo());

		// Guess the option from the class name (Option)Model(View).
		if (empty($this->option))
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
			// Get the fully qualified class name for the current object
			$fqcn = (get_class($this));

			// Explode the name into an array
			$classArray = explode('\\', $fqcn);

			// Get the last element from the array
			$class = array_pop($classArray);

			// Remove Model from the name and store it
			$this->name = str_replace('Model', '', $class);
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

		$this->table = new $class($this->getDb());

		return $this->table;
	}
}
