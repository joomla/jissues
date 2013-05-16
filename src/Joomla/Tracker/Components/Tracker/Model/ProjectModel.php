<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tracker\Components\Tracker\Model;

use Joomla\Database\DatabaseQuery;
use Joomla\Factory;
use Joomla\Tracker\Components\Tracker\Table\ProjectsTable;
use Joomla\Tracker\Model\AbstractTrackerDatabaseModel;
use Joomla\Tracker\Model\AbstractTrackerListModel;

/**
 * Model to get data for the projects list view
 *
 * @package  JTracker\Components\Tracker
 * @since    1.0
 */
class ProjectModel extends AbstractTrackerDatabaseModel
{
	/**
	 * Het an item.
	 *
	 * @param   null  $projectId  The project id.
	 *
	 * @return ProjectsTable
	 */
	public function getItem($projectId = null)
	{
		if (is_null($projectId))
		{
			$projectId = Factory::$application->input->get('project_id');
		}

		$table = new ProjectsTable($this->db);

		return $table->load($projectId);

		/*
		return $this->db->setQuery(
			$this->db->getQuery(true)
		->from($this->db->quoteName('#__tracker_projects', 'a'))
		->select('*')
		->where($this->db->quote(''))
		)
			*/
	}

	/**
	 * Method to get a DatabaseQuery object for retrieving the data set from a database.
	 *
	 * @param   string  $alias  The alias.
	 *
	 * @return  DatabaseQuery   A DatabaseQuery object to retrieve the data set.
	 *
	 * @since   1.0
	 */
	public function getByAlias($alias)
	{
		return $this->db->setQuery(
			$this->db->getQuery(true)
				->from($this->db->quoteName('#__tracker_projects', 'a'))
				->select('*')
				->where($this->db->quoteName('a.alias') . ' = ' . $this->db->quote($alias))
		)
			->loadObject();
	}
}
