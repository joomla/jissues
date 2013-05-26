<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tracker\Components\Tracker\Model;

use Joomla\Factory;
use Joomla\Registry\Registry;
use Joomla\String\String;
use Joomla\Tracker\Components\Tracker\Table\ActivitiesTable;
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

		$item = $this->db->setQuery(
			$this->db->getQuery(true)
				->select('i.*')
				->from($this->db->quoteName('#__issues', 'i'))
				->where($this->db->quoteName('i.project_id') . ' = ' . (int) $projectId)
				->where($this->db->quoteName('i.issue_number') . ' = ' . (int) $identifier)

				// Join over the status table
				->select('s.status AS status_title, s.closed AS closed')
				->join('LEFT', '#__status AS s ON i.status = s.id')

				// Get the relation information
				->select('a1.title AS rel_title, a1.status AS rel_status')
				->join('LEFT', '#__issues AS a1 ON i.rel_id = a1.id')

				// Join over the status table
				->select('s1.closed AS rel_closed')
				->join('LEFT', '#__status AS s1 ON a1.status = s1.id')

				// Join over the status table
				->select('t.name AS rel_name')
				->join('LEFT', '#__issues_relations_types AS t ON i.rel_type = t.id')
		)->loadObject();

		$table = new ActivitiesTable($this->db);
		$query = $this->db->getQuery(true);

		$query->select('a.*');
		$query->from($this->db->quoteName($table->getTableName(), 'a'));
		$query->where($this->db->quoteName('a.project_id') . ' = ' . (int) $projectId);
		$query->where($this->db->quoteName('a.issue_number') . ' = ' . $item->issue_number);
		$query->order($this->db->quoteName('a.created_date'));

		$item->activities = $this->db->setQuery($query)->loadObjectList();

		return $item;
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
