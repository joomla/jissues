<?php
/**
 * Part of the Joomla Tracker's Projects Application
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Projects\Model;

use App\Projects\Table\ProjectsTable;
use App\Projects\TrackerProject;

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
	 * @return  TrackerProject
	 *
	 * @since   1.0
	 */
	public function getItem($projectId = null)
	{
		if (is_null($projectId))
		{
			$app = $this->container->get('app');
			$projectId = $app->input->get('project_id', 1);
		}

		$data = $this->db->setQuery(
			$this->db->getQuery(true)
				->from($this->db->quoteName('#__tracker_projects', 'p'))
				->select('p.*')
				->where($this->db->quoteName('p.project_id') . ' = ' . (int) $projectId)
		)->loadObject();

		return new TrackerProject($this->db, $data);
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
			return new TrackerProject($this->db);
		}

		$data = $this->db->setQuery(
			$this->db->getQuery(true)
				->from($this->db->quoteName('#__tracker_projects', 'p'))
				->select('p.*')
				->where($this->db->quoteName('p.alias') . ' = ' . $this->db->quote($alias))
		)->loadObject();

		return new TrackerProject($this->db, $data);
	}

	/**
	 * Delete a project.
	 *
	 * @param   string  $alias  The project alias.
	 *
	 * @return $this
	 *
	 * @since   1.0
	 */
	public function delete($alias)
	{
		$project = $this->getByAlias($alias);

		$table = new ProjectsTable($this->db);

		$table->delete($project->project_id);

		return $this;
	}
}
