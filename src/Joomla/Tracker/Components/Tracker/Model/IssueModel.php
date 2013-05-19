<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tracker\Components\Tracker\Model;

use Joomla\Factory;
use Joomla\Registry\Registry;
use Joomla\String\String;
use Joomla\Tracker\Components\Tracker\Table\IssuesTable;
use Joomla\Tracker\Components\Tracker\Table\ProjectsTable;
use Joomla\Tracker\Model\AbstractTrackerDatabaseModel;

/**
 * Model to get data for the issue list view
 *
 * @since  1.0
 */
class IssueModel extends AbstractTrackerDatabaseModel
{
	/**
	 * Context string for the model type.  This is used to handle uniqueness
	 * when dealing with the getStoreId() method and caching data structures.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $context = 'com_tracker.issue';

	/**
	 * Get an item.
	 *
	 * @param   integer  $identifier  The item identifier.
	 *
	 * @return  IssuesTable
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	public function getItem($identifier = null)
	{
		if (!$identifier)
		{
			$identifier = Factory::$application->input->getUint('id');

			if (!$identifier)
			{
				throw new \RuntimeException('No id given');
			}
		}

		$projectId = Factory::$application->input->get('project_id');

		if (!$projectId)
		{
			throw new \RuntimeException(__METHOD__ . ' - No project id :(');
		}

		$project = $this->db->setQuery(
			$this->db->getQuery(true)
				->from('#__issues')
				->select('*')
				->where($this->db->quoteName('project_id') . '=' . (int) $projectId)
				->where($this->db->quoteName('gh_id') . '=' . (int) $identifier)
		)->loadObject();

		return $project;
	}

	/**
	 * Get a project.
	 *
	 * @param   integer  $identifier  The project identifier.
	 *
	 * @return  ProjectsTable
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	public function getProject($identifier = null)
	{
		if (!$identifier)
		{
			$identifier = Factory::$application->input->getUint('project_id');

			if (!$identifier)
			{
				throw new \RuntimeException('No id given');
			}
		}

		$table = new ProjectsTable($this->db);

		return $table->load($identifier);
	}
}
