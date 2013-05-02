<?php
/**
 * @copyright   Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tracker\Authentication;

abstract class User
{
	public $id = 0;

	public $username = '';

	public $name = '';

	public $email = '';

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
			throw new \UnexpectedValueException('Load by id not supported yet');

			//$this->load($identifier);
		}
	}

	/**
	 * Method to load a JUser object by user id number
	 *
	 * @param   mixed  $id  The user id of the user to load
	 *
	 * @return  boolean  True on success
	 *
	 * @since   11.1
	 */
	public function load($id)
	{
		// Create the user table object
		$table = $this->getTable();

		// Load the JUserModel object based on the user id or throw a warning.
		if (!$table->load($id))
		{
			// Reset to guest user
			$this->guest = 1;

			JLog::add(JText::sprintf('JLIB_USER_ERROR_UNABLE_TO_LOAD_USER', $id), JLog::WARNING, 'jerror');

			return false;
		}

		/*
		 * Set the user parameters using the default XML file.  We might want to
		 * extend this in the future to allow for the ability to have custom
		 * user parameters, but for right now we'll leave it how it is.
		 */

		$this->_params->loadString($table->params);

		// Assuming all is well at this point let's bind the data
		$this->setProperties($table->getProperties());

		// The user is no longer a guest
		if ($this->id != 0)
		{
			$this->guest = 0;
		}
		else
		{
			$this->guest = 1;
		}

		return true;
	}
}
