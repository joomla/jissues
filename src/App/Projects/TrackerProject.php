<?php
/**
 * Part of the Joomla Tracker's Projects Application
 *
 * @copyright  Copyright (C) 2013 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Projects;

use App\Projects\Table\LabelsTable;
use JTracker\Container;

/**
 * Class TrackerProject.
 *
 * @since  1.0
 */
class TrackerProject
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
	 * External issue tracker link
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $ext_tracker_link;

	/**
	 * Access map
	 *
	 * @var    array
	 * @since  1.0
	 */
	protected $accessMap = array();

	/**
	 * Array containing default actions
	 *
	 * @var    array
	 * @since  1.0
	 */
	private $defaultActions = array('view', 'create', 'edit', 'manage');

	/**
	 * Array containing default user groups
	 *
	 * @var    array
	 * @since  1.0
	 */
	private $defaultGroups = array('Public', 'User');

	/**
	 * Constructor.
	 *
	 * @param   object  $data  The project data.
	 *
	 * @since   1.0
	 * @throws  \UnexpectedValueException
	 */
	public function __construct($data = null)
	{
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
		if (false == in_array($action, $this->defaultActions))
		{
			throw new \InvalidArgumentException(__METHOD__ . ' - Invalid action: ' . $action);
		}

		if ($filter && false == in_array($filter, $this->defaultGroups))
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
		$db = Container::retrieve('db');

		$map = array();

		foreach ($this->defaultGroups as $group)
		{
			$map[$group] = new \stdClass;
		}

		foreach ($this->defaultActions as $action)
		{
			$map['Custom'][$action] = array();

			foreach ($this->defaultGroups as $group)
			{
				$map[$group]->{'can_' . $action} = 0;
			}
		}

		$groups = array();

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

		/* @type \App\Groups\Table\GroupsTable $group */
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
		static $labels = array();

		if (!$labels)
		{
			$db = Container::retrieve('db');

			$table = new LabelsTable($db);

			$labelList = $db ->setQuery(
				$db->getQuery(true)
					->from($db->quoteName($table->getTableName()))
					->select(array('label_id', 'name', 'color'))
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
}
