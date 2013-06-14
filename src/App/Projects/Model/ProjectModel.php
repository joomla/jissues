<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Projects\Model;

use App\Projects\TrackerProject;

use Joomla\Factory;

use JTracker\Model\AbstractTrackerDatabaseModel;

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
	 * @throws \UnexpectedValueException
	 * @since   1.0
	 * @return  TrackerProject
	 */
	public function getItem($projectId = null)
	{
		if (is_null($projectId))
		{
			$projectId = Factory::$application->input->get('project_id', 1);
		}

		$data = $this->db->setQuery(
			$this->db->getQuery(true)
				->from($this->db->quoteName('#__tracker_projects', 'p'))
				->select('p.*')
				->where($this->db->quoteName('p.project_id') . ' = ' . (int) $projectId)
		)->loadObject();

		return new TrackerProject($data);
	}

	/**
	 * Method to get a project by its alias.
	 *
	 * @param   string  $alias  The alias.
	 *
	 * @since   1.0
	 * @return  TrackerProject
	 */
	public function getByAlias($alias = null)
	{
		if (!$alias)
		{
			$alias = Factory::$application->input->get('project_alias');

			if (!$alias)
			{
				return new TrackerProject;
			}
		}

		$data = $this->db->setQuery(
			$this->db->getQuery(true)
				->from($this->db->quoteName('#__tracker_projects', 'p'))
				->select('p.*')
				->where($this->db->quoteName('p.alias') . ' = ' . $this->db->quote($alias))
		)->loadObject();

		return new TrackerProject($data);
	}
}
