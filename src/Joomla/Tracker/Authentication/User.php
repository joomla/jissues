<?php
/**
 * @copyright  Copyright (C) 2013 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tracker\Authentication;

use Joomla\Database\DatabaseDriver;
use Joomla\Date\Date;
use Joomla\Factory;
use Joomla\Tracker\Application\TrackerApplication;
use Joomla\Tracker\Authentication\Database\TableUsers;
use Joomla\Tracker\Authentication\Exception\AuthenticationException;
use Joomla\Tracker\Components\Tracker\Model\ProjectModel;

/**
 * Class containing a user object
 *
 * @since  1.0
 */
abstract class User
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
	 * @var boolean
	 * @since  1.0
	 */
	public $isAdmin = false;

	/**
	 * A list of groups a user has access to.
	 *
	 * @var array
	 * @since  1.0
	 */
	protected $accessGroups = array();

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
	 * @since  1.0
	 * @return TableUsers
	 */
	public function loadByUserName($userName)
	{
		/* @var TrackerApplication $application */
		$application = Factory::$application;

		$database = $application->getDatabase();

		$table = new TableUsers($database);

		$table->loadByUserName($userName);

		if (!$table->id)
		{
			// Register a new user

			$date               = new Date;
			$this->registerDate = $date->format('Y-m-d H:i:s');

			$table->save($this);
		}

		$this->id = $table->id;

		return $this;
	}

	/**
	 * Get available access groups.
	 *
	 * @since  1.0
	 * @return array
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
	 * @return  User
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	protected function load($identifier)
	{
		/* @var TrackerApplication $application */
		$application = Factory::$application;

		$db = $application->getDatabase();

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
	 * @since  1.0
	 * @return $this
	 */
	protected function loadAccessGroups()
	{
		/* @var TrackerApplication $application */
		$application = Factory::$application;

		$db = $application->getDatabase();

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
	 * @return boolean
	 */
	public function check($action)
	{
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
	 * Authorize a given action.
	 *
	 * @param   string  $action  The action.
	 *
	 * @throws \InvalidArgumentException
	 * @throws AuthenticationException
	 *
	 * @since   1.0
	 * @return  User
	 */
	public function authorize($action)
	{
		static $cleared = array();

		if (in_array($action, $cleared))
		{
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
			throw new AuthenticationException($this, $action);
		}

		if (false == in_array($action, array('view', 'create', 'edit')))
		{
			throw new \InvalidArgumentException('Undefined action: ' . $action);
		}

		$projectModel = new ProjectModel;

		$project = $projectModel->getItem();

		if ($projectModel->getAccessGroups($project->project_id, $action, 'Public'))
		{
			// Project has public access for the action.
			$cleared[] = $action;

			return $this;
		}

		if ($this->id)
		{
			if ($projectModel->getAccessGroups($project->project_id, $action, 'User'))
			{
				// Project has User access for the action.
				$cleared[] = $action;

				return $this;
			}

			$groups = $projectModel->getAccessGroups($project->project_id, $action);

			foreach ($groups as $group)
			{
				if (in_array($group, $this->accessGroups))
				{
					// The user is member of the group.
					$cleared[] = $action;

					return $this;
				}
			}
		}

		throw new AuthenticationException($this, $action);
	}
}
