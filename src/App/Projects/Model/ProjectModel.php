<?php
/**
 * Part of the Joomla Tracker's Projects Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
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
			$app = $this->getContainer()->get('app');
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
	 * @throws  \UnexpectedValueException
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

		if (!$data)
		{
			throw new \UnexpectedValueException('This project does not exist.', 404);
		}

		return new TrackerProject($this->db, $data);
	}

	/**
	 * Delete a project.
	 *
	 * @param   string  $alias  The project alias.
	 *
	 * @return  $this  Method allows chaining
	 *
	 * @since   1.0
	 */
	public function delete($alias)
	{
		$project = $this->getByAlias($alias);

		// Delete access groups associated with the project
		$this->db->setQuery(
			$this->db->getQuery(true)
				->delete($this->db->quoteName('#__accessgroups'))
				->where($this->db->quoteName('project_id') . '=' . (int) $project->project_id)
		)->execute();

		// @todo: cleanup more.

		// Delete the project
		(new ProjectsTable($this->db))
			->delete($project->project_id);

		return $this;
	}
}
