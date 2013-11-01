<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Tracker\Model;

use App\Projects\Table\ProjectsTable;
use App\Tracker\Table\ActivitiesTable;
use App\Tracker\Table\IssuesTable;

use Joomla\Filter\InputFilter;
use Joomla\Registry\Registry;
use Joomla\String\String;

use JTracker\Model\AbstractTrackerDatabaseModel;
use JTracker\Container;

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
		$app = Container::retrieve('app');

		if (!$identifier)
		{
			$identifier = $app->input->getUint('id');

			if (!$identifier)
			{
				throw new \RuntimeException('No id given');
			}
		}

		$project = $app->getProject();

		$item = $this->db->setQuery(
			$this->db->getQuery(true)
				->select('i.*')
				->from($this->db->quoteName('#__issues', 'i'))
				->where($this->db->quoteName('i.project_id') . ' = ' . (int) $project->project_id)
				->where($this->db->quoteName('i.issue_number') . ' = ' . (int) $identifier)

				// Join over the status table
				->select($this->db->quoteName('s.status', 'status_title'))
				->select($this->db->quoteName('s.closed', 'closed'))
				->leftJoin(
					$this->db->quoteName('#__status', 's')
					. ' ON '
					. $this->db->quoteName('i.status')
					. ' = ' . $this->db->quoteName('s.id')
				)

				// Get the relation information
				->select('a1.title AS rel_title, a1.status AS rel_status')
				->join('LEFT', '#__issues AS a1 ON i.rel_number = a1.issue_number')

				// Join over the status table
				->select('s1.closed AS rel_closed')
				->join('LEFT', '#__status AS s1 ON a1.status = s1.id')

				// Join over the relations_types table
				->select('t.name AS rel_name')
				->join('LEFT', '#__issues_relations_types AS t ON i.rel_type = t.id')

				// Join over the issues_voting table
				->select('v.votes, v.experienced, v.score')
				->join('LEFT', '#__issues_voting AS v ON i.vote_id = v.id')
		)->loadObject();

		if (!$item)
		{
			throw new \RuntimeException('Invalid Issue');
		}

		// Fetch activities
		$table = new ActivitiesTable($this->db);
		$query = $this->db->getQuery(true);

		$query->select('a.*');
		$query->from($this->db->quoteName($table->getTableName(), 'a'));
		$query->where($this->db->quoteName('a.project_id') . ' = ' . (int) $project->project_id);
		$query->where($this->db->quoteName('a.issue_number') . ' = ' . (int) $item->issue_number);
		$query->order($this->db->quoteName('a.created_date'));

		$item->activities = $this->db->setQuery($query)->loadObjectList();

		// Fetch foreign relations
		$item->relations_f = $this->db->setQuery(
				$this->db->getQuery(true)
					->from($this->db->quoteName('#__issues', 'a'))
					->join('LEFT', '#__issues_relations_types AS t ON a.rel_type = t.id')
					->join('LEFT', '#__status AS s ON a.status = s.id')
					->select('a.issue_number, a.title, a.rel_type')
					->select('t.name AS rel_name')
					->select('s.status AS status_title, s.closed AS closed')
					->where($this->db->quoteName('a.rel_number') . '=' . (int) $item->issue_number)
					->order(array('a.issue_number', 'a.rel_type'))
			)->loadObjectList();

		// Group relations by type
		if ($item->relations_f)
		{
			$arr = array();

			foreach ($item->relations_f as $relation)
			{
				if (false == isset($arr[$relation->rel_name]))
				{
					$arr[$relation->rel_name] = array();
				}

				$arr[$relation->rel_name][] = $relation;
			}

			$item->relations_f = $arr;
		}

		// Set the score if we have the vote_id
		if ($item->vote_id)
		{
			$item->importanceScore = $item->score / $item->votes;
		}
		else
		{
			$item->importanceScore = 0;
		}

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
			$app = Container::retrieve('app');
			$identifier = $app->input->getUint('project_id');

			if (!$identifier)
			{
				throw new \RuntimeException('No id given');
			}
		}

		$table = new ProjectsTable($this->db);

		return $table->load($identifier);
	}

	/**
	 * Get a status list.
	 *
	 * @return  array
	 *
	 * @since   1.0
	 */
	public function getStatuses()
	{
		return $this->db->setQuery(
			$this->db->getQuery(true)
				->from($this->db->quoteName('#__status'))
				->select('*')
		)->loadObjectList();
	}

	/**
	 * Add the item.
	 *
	 * @param   array  $src  The source.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	public function add(array $src)
	{
		$filter = new InputFilter;

		$src['description_raw'] = $filter->clean($src['description_raw'], 'string');

		// Store the issue
		$table = new IssuesTable($this->db);

		$table->save($src);

		// Store the activity
		$table = new ActivitiesTable($this->db);

		$src['event']   = 'open';
		$src['user']    = $src['opened_by'];

		$table->save($src);

		return $this;
	}

	/**
	 * Save the item.
	 *
	 * @param   array  $src  The source.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	public function save(array $src)
	{
		$filter = new InputFilter;

		$data = array();

		$data['id']              = $filter->clean($src['id'], 'int');
		$data['status']          = $filter->clean($src['status'], 'int');
		$data['priority']        = $filter->clean($src['priority'], 'int');
		$data['title']           = $filter->clean($src['title'], 'string');
		$data['build']           = $filter->clean($src['build'], 'string');
		$data['description_raw'] = $filter->clean($src['description_raw'], 'string');
		$data['rel_number']      = $filter->clean($src['rel_number'], 'int');
		$data['rel_type']        = $filter->clean($src['rel_type'], 'int');

		if (!$data['id'])
		{
			throw new \RuntimeException('Missing ID');
		}

		$table = new IssuesTable($this->db);

		$table->load($data['id'])
			->save($data);

		return $this;
	}

	/**
	 * Update vote data for an issue
	 *
	 * @param   integer  $id           The issue ID
	 * @param   integer  $experienced  Whether the user has experienced the issue
	 * @param   integer  $importance   The importance of the issue to the user
	 *
	 * @return  object
	 *
	 * @since   1.0
	 */
	public function vote($id, $experienced, $importance)
	{
		$db = $this->getDb();

		$table = new IssuesTable($db);
		$table->load($id);

		// Insert a new record if no vote_id is associated
		if (is_null($table->vote_id))
		{
			$columnsArray = array(
				$db->quoteName('votes'),
				$db->quoteName('experienced'),
				$db->quoteName('score')
			);

			$query = $db->getQuery()
				->insert($db->quoteName('#__issues_voting'))
				->columns($columnsArray)
				->values(
					'1, '
					. $experienced . ', '
					. $importance
				);
		}
		else
		{
			$query = $db->getQuery()
				->update($db->quoteName('#__issues_voting'))
				->set($db->quoteName('votes') . ' = ' . $db->quoteName('votes') . ' + 1')
				->set($db->quoteName('experienced') . ' = ' . $db->quoteName('experienced') . ' + ' . $experienced)
				->set($db->quoteName('score') . ' = ' . $db->quoteName('score') . ' + ' . $importance)
				->where($db->quoteName('id') . ' = ' . (int) $table->vote_id);
		}

		$db->setQuery($query)->execute();

		// Add the vote_id if a new record
		if (is_null($table->vote_id))
		{
			$query = $db->getQuery()
				->update($db->quoteName('#__issues'))
				->set($db->quoteName('vote_id') . ' = ' . $db->insertid())
				->where($db->quoteName('id') . ' = ' . (int) $table->id);

			$db->setQuery($query)->execute();
		}

		// Get the updated vote data to update the display
		$query->clear()
			->select('*')
			->from($db->quoteName('#__issues_voting'))
			->where($db->quoteName('id') . ' = ' . (int) $table->id);

		return $db->setQuery($query)->loadObject();
	}
}
