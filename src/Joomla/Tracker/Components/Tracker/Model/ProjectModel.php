<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tracker\Components\Tracker\Model;

use Joomla\Factory;
use Joomla\Tracker\Components\Tracker\TrackerProject;
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

		if (!$projectId)
		{
			throw new \UnexpectedValueException('No project id');
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
	public function getByAlias($alias)
	{
		$data = $this->db->setQuery(
			$this->db->getQuery(true)
				->from($this->db->quoteName('#__tracker_projects', 'p'))
				->select('p.*')
				->where($this->db->quoteName('p.alias') . ' = ' . $this->db->quote($alias))
		)->loadObject();

		return new TrackerProject($data);
	}
}
