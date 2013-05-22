<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tracker\Components\Tracker\Model;

use Joomla\Factory;
use Joomla\Tracker\Components\Tracker\Table\ProjectsTable;
use Joomla\Tracker\Model\AbstractTrackerDatabaseModel;

/**
 * Model to get data for the project list view
 *
 * @since  1.0
 */
class ProjectModel extends AbstractTrackerDatabaseModel
{
	/**
	 * Get an item.
	 *
	 * @param   integer  $projectId  The project id.
	 *
	 * @return  ProjectsTable
	 *
	 * @since   1.0
	 */
	public function getItem($projectId = null)
	{
		if (is_null($projectId))
		{
			$projectId = Factory::$application->input->get('project_id', 1);
		}

		$table = new ProjectsTable($this->db);

		return $table->load($projectId);
	}

	/**
	 * Method to get a project by its alias.
	 *
	 * @param   string  $alias  The alias.
	 *
	 * @return  ProjectsTable
	 *
	 * @since   1.0
	 */
	public function getByAlias($alias)
	{
		return $this->db->setQuery(
			$this->db->getQuery(true)
				->from($this->db->quoteName('#__tracker_projects', 'p'))
				->select($this->db->quoteName('p.project_id'))
				->where($this->db->quoteName('p.alias') . ' = ' . $this->db->quote($alias))
		)
			->loadObject();
	}

	/**
	 * Get the access groups for a project.
	 *
	 * NOTE: It is intended that this method is coupled to the project model ;)
	 *
	 * @param   integer  $projectId  The project id.
	 * @param   string   $action     The action.
	 * @param   string   $filter     The filter.
	 *
	 * @throws \UnexpectedValueException
	 * @throws \InvalidArgumentException
	 *
	 * @return mixed
	 */
	public function getAccessGroups($projectId, $action, $filter = '')
	{
		if (false == in_array($action, array('view', 'create', 'edit')))
		{
			throw new \InvalidArgumentException(__METHOD__ . ' - Invalid action: ' . $action);
		}

		if ($filter && false == in_array($filter, array('Public', 'User')))
		{
			throw new \InvalidArgumentException(__METHOD__ . ' - Invalid filter: ' . $filter);
		}

		$query = $this->db->getQuery(true)
			->from($this->db->quoteName('#__accessgroups'))
			->select('group_id')
			->where($this->db->quoteName('project_id') . ' = ' . (int) $projectId)
			->where($this->db->quoteName('can_' . $action) . ' = 1');

		if ($filter)
		{
			// Get a "system group"
			$query->where($this->db->quoteName('title') . ' = ' . $this->db->quote($filter));
		}
		else
		{
			// Get only "custom groups"
			$query->where(
				$this->db->quoteName('title')
				. ' NOT IN ('
				. $this->db->quote('Public') . ','
				. $this->db->quote('User')
				. ')'
			);
		}

		$groups = $this->db->setQuery($query)
			->loadRow();

		if (!$groups)
		{
			// PANIC

			return array();
		}

		return $groups;
	}
}
