<?php
/**
 * @copyright  Copyright (C) 2013 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tracker\Components\Tracker;

use Joomla\Factory;

/**
 * Class TrackerProject.
 *
 * @property   integer  $project_id        PK
 * @property   string   $title             Project title
 * @property   string   $alias             Project URL alias
 * @property   string   $gh_user           GitHub user
 * @property   string   $gh_project        GitHub project
 * @property   string   $ext_tracker_link  A tracker link format (e.g. http://tracker.com/issue/%d)
 *
 * @property   array    $accessGroups
 *
 * @since  1.0
 */
class TrackerProject
{
	/**
	 * PK
	 *
	 * @var  integer
	 */
	protected $project_id = 0;

	//             Project title
	protected $title;

	//             Project URL alias
	protected $alias;

	//           GitHub user
	protected $gh_user;

	//        GitHub project
	protected $gh_project;

	//  A tracker link format (e.g. http://tracker.com/issue/%d)
	protected $ext_tracker_link;

	protected $accessMap = array();

	private $defaultActions = array('view', 'create', 'edit', 'manage');

	private $defaultGroups = array('Public', 'User');

	/**
	 * Constructor.
	 *
	 * @param   object  $data  The project data.
	 *
	 * @throws \UnexpectedValueException
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
	 * @return mixed
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
	 * @throws \InvalidArgumentException
	 *
	 * @return array
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
	 * @since  1.0
	 * @throws \RuntimeException
	 * @return array
	 */
	protected function loadMap()
	{
		/* @type \Joomla\Tracker\Application\TrackerApplication $application */
		$application = Factory::$application;

		$db = $application->getDatabase();

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

		/* @type \Joomla\Tracker\Components\Tracker\Table\GroupsTable $group */
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

	/*
	 * NOTE: The following functions have been added to make the properties "visible"
	 * for the Twig template engine.
	 */

	/**
	 * Get the project id.
	 *
	 * @since  1.0
	 * @return integer
	 */
	public function getProject_Id()
	{
		return $this->project_id;
	}

	/**
	 * Get the project title.
	 *
	 * @since  1.0
	 * @return string
	 */
	public function getTitle()
	{
		return $this->title;
	}

	/**
	 * Get the project URL alias.
	 *
	 * @since  1.0
	 * @return string
	 */
	public function getAlias()
	{
		return $this->alias;
	}

	/**
	 * Get the GitHub user (owner).
	 *
	 * @since  1.0
	 * @return string
	 */
	public function getGh_User()
	{
		return $this->gh_user;
	}

	/**
	 * Get the GitHub project (repo).
	 *
	 * @since  1.0
	 * @return string
	 */
	public function getGh_Project()
	{
		return $this->gh_project;
	}

	/**
	 * Get the external link.
	 *
	 * @since  1.0
	 * @return string
	 */
	public function getExt_Tracker_Link()
	{
		return $this->ext_tracker_link;
	}
}
