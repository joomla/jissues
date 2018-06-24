<?php
/**
 * Part of the Joomla Tracker's Projects Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Projects;

use App\Projects\Table\LabelsTable;
use App\Projects\Table\MilestonesTable;

use Joomla\Database\DatabaseDriver;

/**
 * Class TrackerProject.
 *
 * @property-read   integer  $project_id        PK
 * @property-read   string   $title             Project title
 * @property-read   string   $alias             Project URL alias
 * @property-read   string   $gh_user           GitHub user
 * @property-read   string   $gh_project        GitHub project
 * @property-read   string   $gh_editbot_user   GitHub editbot username.
 * @property-read   string   $gh_editbot_pass   GitHub editbot password.
 * @property-read   string   $ext_tracker_link  A tracker link format (e.g. http://tracker.com/issue/%d)
 * @property-read   string   $short_title       Project short title
 *
 * @since  1.0
 */
class TrackerProject implements \Serializable
{
	/**
	 * Primary Key
	 *
	 * @var    integer
	 * @since  1.0
	 */
	protected $project_id = 0;

	/**
	 * Project title
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $title;

	/**
	 * Project URL alias
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $alias;

	/**
	 * GitHub User
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $gh_user;

	/**
	 * GitHub Project
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $gh_project;

	/**
	 * GitHub edit bot user name.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $gh_editbot_user;

	/**
	 * GitHub edit bot password.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $gh_editbot_pass;

	/**
	 * External issue tracker link
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $ext_tracker_link;

	/**
	 * Project short title
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $short_title;

	/**
	 * Access map
	 *
	 * @var    array
	 * @since  1.0
	 */
	protected $accessMap = [];

	/**
	 * Array containing default actions
	 *
	 * @var    array
	 * @since  1.0
	 */
	private $defaultActions = ['view', 'create', 'edit', 'editown', 'manage'];

	/**
	 * Array containing default user groups
	 *
	 * @var    array
	 * @since  1.0
	 */
	private $defaultGroups = ['Public', 'User'];

	/**
	 * @var    DatabaseDriver
	 * @since  1.0
	 */
	private $database = null;

	/**
	 * Constructor.
	 *
	 * @param   DatabaseDriver  $database  The database connector.
	 * @param   object          $data      The project data.
	 *
	 * @since   1.0
	 * @throws  \UnexpectedValueException
	 */
	public function __construct(DatabaseDriver $database, $data = null)
	{
		$this->setDatabase($database);

		if (is_null($data))
		{
			return;
		}

		foreach ($data as $key => $value)
		{
			if (isset($this->$key) || is_null($this->$key))
			{
				$this->$key = $value;

				continue;
			}

			throw new \UnexpectedValueException(__METHOD__ . ' - unexpected key: ' . $key);
		}
	}

	/**
	 * Get a value.
	 *
	 * @param   string  $key  The key name
	 *
	 * @return  mixed
	 *
	 * @since   1.0
	 */
	public function __get($key)
	{
		if (isset($this->$key))
		{
			return $this->$key;
		}

		return 'not set..';
	}

	/**
	 * Get the access groups for a project.
	 *
	 * NOTE: It is intended that this method is coupled to the project model ;)
	 *
	 * @param   string  $action  The action.
	 * @param   string  $filter  The filter.
	 *
	 * @return  array
	 *
	 * @since   1.0
	 * @throws  \InvalidArgumentException
	 */
	public function getAccessGroups($action, $filter = '')
	{
		if (false === in_array($action, $this->defaultActions))
		{
			throw new \InvalidArgumentException(__METHOD__ . ' - Invalid action: ' . $action);
		}

		if ($filter && false === in_array($filter, $this->defaultGroups))
		{
			throw new \InvalidArgumentException(__METHOD__ . ' - Invalid filter: ' . $filter);
		}

		if (!$this->accessMap)
		{
			$this->accessMap = $this->loadMap();
		}

		if ($filter)
		{
			return $this->accessMap[$filter]->{'can_' . $action};
		}

		return $this->accessMap['Custom'][$action];
	}

	/**
	 * Load the access map for the project.
	 *
	 * This method is supposed to be called only once per session or, if the project changes.
	 *
	 * @return  array
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	protected function loadMap()
	{
		$db = $this->database;

		$map = [];

		foreach ($this->defaultGroups as $group)
		{
			$map[$group] = new \stdClass;
		}

		foreach ($this->defaultActions as $action)
		{
			$map['Custom'][$action] = [];

			foreach ($this->defaultGroups as $group)
			{
				$map[$group]->{'can_' . $action} = 0;
			}
		}

		$groups = [];

		if ($this->project_id)
		{
			// Only for existing projects
			$groups = $db->setQuery(
				$db->getQuery(true)
					->from($db->quoteName('#__accessgroups'))
					->select('*')
					->where($db->quoteName('project_id') . ' = ' . (int) $this->project_id)
			)->loadObjectList();

			if (!$groups)
			{
				// PANIC - There must be at least two system groups.
				throw new \RuntimeException('No project groups defined.');
			}
		}

		/** @var \App\Groups\Table\GroupsTable $group */
		foreach ($groups as $group)
		{
			// Process a system group.
			if ($group->system)
			{
				$m = new \stdClass;

				foreach ($this->defaultActions as $action)
				{
					$m->{'can_' . $action} = $group->{'can_' . $action};
				}

				$map[$group->title] = $m;

				continue;
			}

			// Process a custom group.
			foreach ($this->defaultActions as $action)
			{
				if ($group->{'can_' . $action})
				{
					// If the action is allowed, add it to the map.
					$map['Custom'][$action][] = $group->group_id;
				}
			}
		}

		return $map;
	}

	/**
	 * Get a list of labels defined for the project.
	 *
	 * @return  array
	 *
	 * @since   1.0
	 */
	public function getLabels()
	{
		static $labels = [];

		if (!$labels)
		{
			$db = $this->database;

			$labelList = $db ->setQuery(
				$db->getQuery(true)
					->from($db->quoteName((new LabelsTable($db))->getTableName()))
					->select(['label_id', 'name', 'color'])
					->where($db->quoteName('project_id') . ' = ' . $this->project_id)
			)->loadObjectList();

			foreach ($labelList as $labelObject)
			{
				$l = new \stdClass;

				$l->name  = $labelObject->name;
				$l->color = $labelObject->color;

				$labels[$labelObject->label_id] = $l;
			}
		}

		return $labels;
	}

	/**
	 * Get a list of labels defined for the project.
	 *
	 * @return  array
	 *
	 * @since   1.0
	 */
	public function getMilestones()
	{
		static $milestones = [];

		if (!$milestones)
		{
			$db = $this->database;

			$milestones = $db ->setQuery(
				$db->getQuery(true)
					->from($db->quoteName((new MilestonesTable($db))->getTableName()))
					->select(['milestone_id', 'milestone_number', 'title', 'description', 'state', 'due_on'])
					->where($db->quoteName('project_id') . ' = ' . $this->project_id)
					->order($db->quoteName('title'))
			)->loadObjectList();
		}

		return $milestones;
	}

	/*
	 * NOTE: The following functions have been added to make the properties "visible"
	 * for the Twig template engine.
	 */

	/**
	 * Get the project id.
	 *
	 * @return  integer
	 *
	 * @since   1.0
	 */
	public function getProject_Id()
	{
		return $this->project_id;
	}

	/**
	 * Get the project title.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	public function getTitle()
	{
		return $this->title;
	}

	/**
	 * Get the project URL alias.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	public function getAlias()
	{
		return $this->alias;
	}

	/**
	 * Get the GitHub user (owner).
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	public function getGh_User()
	{
		return $this->gh_user;
	}

	/**
	 * Get the GitHub project (repo).
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	public function getGh_Project()
	{
		return $this->gh_project;
	}

	/**
	 * Get the external link.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	public function getExt_Tracker_Link()
	{
		return $this->ext_tracker_link;
	}

	/**
	 * Get the project short title.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	public function getShort_Title()
	{
		return $this->short_title;
	}

	/**
	 * Method to set the database connector.
	 *
	 * @param   DatabaseDriver  $database  The database connector.
	 *
	 * @return  void
	 *
	 * @since 1.0
	 */
	public function setDatabase(DatabaseDriver $database)
	{
		$this->database = $database;
	}

	/**
	 * String representation of object
	 *
	 * @return  string  The string representation of the object or null
	 *
	 * @link    http://php.net/manual/en/serializable.serialize.php
	 * @since   1.0
	 */
	public function serialize()
	{
		$props = [];

		foreach (get_object_vars($this) as $key => $value)
		{
			if (in_array($key, ['authModel', 'cleared', 'authId', 'database']))
			{
				continue;
			}

			$props[$key] = $value;
		}

		return serialize($props);
	}

	/**
	 * Constructs the object
	 *
	 * @param   string  $serialized  The string representation of the object.
	 *
	 * @return  void
	 *
	 * @link    http://php.net/manual/en/serializable.unserialize.php
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

	/**
	 * Get the default actions.
	 *
	 * @return array
	 *
	 * @since   1.0
	 */
	public function getDefaultActions()
	{
		return $this->defaultActions;
	}

	/**
	 * Get the edit bot username.
	 *
	 * @return string
	 *
	 * @since   1.0
	 */
	public function getGh_Editbot_User()
	{
		return $this->gh_editbot_user;
	}

	/**
	 * Get the edit bot password.
	 *
	 * @return string
	 *
	 * @since   1.0
	 */
	public function getGh_Editbot_Pass()
	{
		return $this->gh_editbot_pass;
	}

	/**
	 * Get Categories list object for displaying
	 *
	 * @return  array
	 *
	 * @since    1.0
	 */
	public function getCategories()
	{
		static $categories;

		if (!$categories)
		{
			$db    = $this->database;
			$query = $db->getQuery(true);

			$query
				->select('*')
				->from($db->quoteName('#__issues_categories'))
				->where($db->quoteName('project_id') . ' = ' . $this->project_id)
				->order($db->quoteName('title'));

			$categories = $db->setQuery($query)->loadObjectList();
		}

		return $categories;
	}
}
