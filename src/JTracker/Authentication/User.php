<?php
/**
 * Part of the Joomla Tracker Authentication Package
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JTracker\Authentication;

use Joomla\Database\DatabaseDriver;
use Joomla\Date\Date;

use JTracker\Application\TrackerApplication;
use JTracker\Authentication\Database\TableUsers;
use JTracker\Authentication\Exception\AuthenticationException;
use JTracker\Container;

/**
 * Abstract class containing the application user object
 *
 * @since  1.0
 */
abstract class User implements \Serializable
{
	/**
	 * @var    integer
	 * @since  1.0
	 */
	public $id = 0;

	/**
	 * @var    string
	 * @since  1.0
	 */
	public $username = '';

	/**
	 * @var    string
	 * @since  1.0
	 */
	public $name = '';

	/**
	 * @var    string
	 * @since  1.0
	 */
	public $email = '';

	/**
	 * @var    string
	 * @since  1.0
	 */
	public $registerDate = '';

	/**
	 * If a user has special "admin" rights.
	 *
	 * @var    boolean
	 * @since  1.0
	 */
	public $isAdmin = false;

	/**
	 * A list of groups a user has access to.
	 *
	 * @var    array
	 * @since  1.0
	 */
	protected $accessGroups = array();

	/**
	 * A list of cleared access rights.
	 *
	 * @var    array
	 * @since  1.0
	 */
	private $cleared = array();

	/**
	 * Constructor.
	 *
	 * @param   integer  $identifier  The primary key of the user to load..
	 *
	 * @since   1.0
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
	 * Load data by a given user name.
	 *
	 * @param   string  $userName  The user name
	 *
	 * @return  TableUsers
	 *
	 * @since   1.0
	 */
	public function loadByUserName($userName)
	{
		$db = Container::retrieve('db');

		$table = new TableUsers($db);

		$table->loadByUserName($userName);

		if (!$table->id)
		{
			// Register a new user
			$date               = new Date;
			$this->registerDate = $date->format($db->getDateFormat());

			$table->save($this);
		}

		$this->id = $table->id;

		$this->loadAccessGroups();

		return $this;
	}

	/**
	 * Get available access groups.
	 *
	 * @return  array
	 *
	 * @since   1.0
	 */
	public function getAccessGroups()
	{
		return $this->accessGroups;
	}

	/**
	 * Method to load a User object by user id number.
	 *
	 * @param   mixed  $identifier  The user id of the user to load.
	 *
	 * @return  $this  Method allows chaining
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	protected function load($identifier)
	{
		$db = Container::retrieve('db');

		// Create the user table object
		// $table = $this->getTable();
		$table = new TableUsers($db);

		// Load the JUserModel object based on the user id or throw a warning.
		if (!$table->load($identifier))
		{
			throw new \RuntimeException('Unable to load the user with id: ' . $identifier);
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

		// $doo = array_keys($table->getFields());

		foreach ($table->getFields() as $key => $vlaue)
		{
			if (isset($this->$key))
			{
				$this->$key = $table->$key;
			}
		}

		$this->loadAccessGroups();

		return $this;
	}

	/**
	 * Load the access groups.
	 *
	 * @return  $this  Method allows chaining
	 *
	 * @since   1.0
	 */
	protected function loadAccessGroups()
	{
		$db = Container::retrieve('db');

		$this->accessGroups = $db->setQuery(
			$db->getQuery(true)
				->from($db->quoteName('#__user_accessgroup_map'))
				->select($db->quoteName('group_id'))
				->where($db->quoteName('user_id') . '=' . (int) $this->id)
		)->loadColumn();

		return $this;
	}

	/**
	 * Check if a user is authorized to perform a given action.
	 *
	 * @param   string  $action  The action to check.
	 *
	 * @return  boolean
	 *
	 * @since   1.0
	 */
	public function check($action)
	{
		if (array_key_exists($action, $this->cleared))
		{
			return $this->cleared[$action] ? true : false;
		}

		try
		{
			$this->authorize($action);

			return true;
		}
		catch (AuthenticationException $e)
		{
			return false;
		}
	}

	/**
	 * Check if the user is authorized to perform a given action.
	 *
	 * @param   string  $action  The action.
	 *
	 * @return  $this  Method allows chaining
	 *
	 * @since   1.0
	 * @throws  \InvalidArgumentException
	 * @throws  AuthenticationException
	 */
	public function authorize($action)
	{
		if (array_key_exists($action, $this->cleared))
		{
			if (0 == $this->cleared[$action])
			{
				throw new AuthenticationException($this, $action);
			}

			return $this;
		}

		if ($this->isAdmin)
		{
			// "Admin users" are granted all permissions - globally.
			return $this;
		}

		if ('admin' == $action)
		{
			// "Admin action" requested for non "Admin user".
			$this->cleared[$action] = 0;
			throw new AuthenticationException($this, $action);
		}

		if (false == in_array($action, array('view', 'create', 'edit', 'manage')))
		{
			throw new \InvalidArgumentException('Undefined action: ' . $action);
		}

		/* @type \App\Projects\TrackerProject $project */
		/* @type TrackerApplication $app */
		$app = Container::retrieve('app');
		$project = $app->getProject();

		if ($project->getAccessGroups($action, 'Public'))
		{
			// Project has public access for the action.
			$this->cleared[$action] = 1;

			return $this;
		}

		if ($this->id)
		{
			if ($project->getAccessGroups($action, 'User'))
			{
				// Project has User access for the action.
				$this->cleared[$action] = 1;

				return $this;
			}

			// Check if a User has access to a custom group
			$groups = $project->getAccessGroups($action);

			foreach ($groups as $group)
			{
				if (in_array($group, $this->accessGroups))
				{
					// The User is member of the group.
					$this->cleared[$action] = 1;

					return $this;
				}
			}
		}

		$this->cleared[$action] = 0;

		throw new AuthenticationException($this, $action);
	}

	/**
	 * Serialize the object
	 *
	 * @return  string  The string representation of the object or null
	 *
	 * @since   1.0
	 */
	public function serialize()
	{
		$props = array();

		foreach (get_object_vars($this) as $key => $value)
		{
			if (in_array($key, array('authModel', 'cleared', 'authId')))
			{
				continue;
			}

			$props[$key] = $value;
		}

		return serialize($props);
	}

	/**
	 * Unserialize the object
	 *
	 * @param   string  $serialized  The serialized string
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function unserialize($serialized)
	{
		$data = unserialize($serialized);

		foreach ($data as $key => $value)
		{
			$this->$key = $value;
		}
	}
}
