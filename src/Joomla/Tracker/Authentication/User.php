<?php
/**
 * @copyright   Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tracker\Authentication;

use Joomla\Factory;
use Joomla\Tracker\Authentication\Database\TableUsers;

abstract class User
{
	public $id = 0;

	public $username = '';

	public $name = '';

	public $email = '';

	public $registerDate = '';

	/**
	 * Constructor activating the default information of the language
	 *
	 * @param   integer  $identifier  The primary key of the user to load (optional).
	 *
	 * @since   11.1
	 */
	public function __construct($identifier = 0)
	{
		// Load the user if it exists
		if ($identifier)
		{
			$this->load($identifier);
		}
	}

	/**
	 * Method to load a JUser object by user id number
	 *
	 * @param   mixed $id  The user id of the user to load
	 *
	 * @throws \RuntimeException
	 * @since   1.0
	 *
	 * @return $this
	 */
	protected function load($id)
	{
		// Create the user table object
		//$table = $this->getTable();
		$table = new TableUsers(Factory::$application->getDatabase());

		// Load the JUserModel object based on the user id or throw a warning.
		if (!$table->load($id))
		{
			throw new \RuntimeException('Unable to load the user with id: ' . $id);
		}

		/*
		 * Set the user parameters using the default XML file.  We might want to
		 * extend this in the future to allow for the ability to have custom
		 * user parameters, but for right now we'll leave it how it is.
		 *
		 * EDIT (elkuku): right now it's disabled =;)
		 */

		// $this->_params->loadString($table->params);

		// Assuming all is well at this point let's bind the data

		foreach ($table->getFields() as $k => $v)
		{
			if (isset($this->$k))
			{
				$this->$k = $table->$k;
			}
		}

		return $this;
	}
}
