<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tracker\Authentication;

use Joomla\Factory;
use Joomla\Tracker\Authentication\Database\TableUsers;
use Joomla\Tracker\Authentication\Exception\AuthenticationException;

/**
 * Class User.
 *
 * @since  1.0
 */
abstract class User
{
	/**
	 * @var integer
	 */
	public $id = 0;

	/**
	 * @var string
	 */
	public $username = '';

	/**
	 * @var string
	 */
	public $name = '';

	/**
	 * @var string
	 */
	public $email = '';

	/**
	 * @var string
	 */
	public $registerDate = '';

	/**
	 * Constructor.
	 *
	 * @param   integer  $identifier  The primary key of the user to load..
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
	 * Method to load a User object by user id number.
	 *
	 * @param   mixed  $id  The user id of the user to load.
	 *
	 * @throws \RuntimeException
	 * @since   1.0
	 *
	 * @return $this
	 */
	protected function load($id)
	{
		// Create the user table object
		// $table = $this->getTable();
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

		foreach ($table->getFields() as $key => $vlaue)
		{
			if (isset($this->$key))
			{
				$this->$key = $table->$key;
			}
		}

		return $this;
	}

	/**
	 * Authorize a given action.
	 *
	 * @param   string  $action  The action.
	 *
	 * @throws Exception\AuthenticationException
	 *
	 * @return $this
	 */
	public function authorize($action)
	{
		$adminUsers = Factory::$application->get('acl.admin_users');

		$adminUsers = ($adminUsers) ? explode(',', $adminUsers) : array();

		if (in_array($this->username, $adminUsers))
		{
			// "Admin users" are granted all permissions.

			return $this;
		}

		switch ($action)
		{
			// @todo - group based ACL
		}

		throw new AuthenticationException($this, $action);
	}
}
