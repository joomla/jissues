<?php
/**
 * Part of the Joomla Tracker's Projects Application
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Projects\Model;

use App\Projects\TrackerProject;

use JTracker\Model\AbstractTrackerDatabaseModel;
use JTracker\Container;

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
	 * @return  TrackerProject
	 *
	 * @since   1.0
	 */
	public function getItem($projectId = null)
	{
		if (is_null($projectId))
		{
			$app = Container::retrieve('app');
			$projectId = $app->input->get('project_id', 1);
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
	 * @return  TrackerProject
	 *
	 * @since   1.0
	 */
	public function getByAlias($alias = null)
	{
		if (!$alias)
		{
			$app = Container::retrieve('app');
			$alias = $app->input->get('project_alias');

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
