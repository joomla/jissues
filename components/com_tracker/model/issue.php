<?php
/**
 * @package     JTracker
 * @subpackage  com_tracker
 *
 * @copyright   Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Model to get data for the issue detail view
 *
 * @package     JTracker
 * @subpackage  Model
 * @since       1.0
 */
class TrackerModelIssue extends JModelTrackerForm
{
	/**
	 * Instantiate the model.
	 *
	 * @since  1.0
	 */
	public function __construct()
	{
		// Set the name
		$this->name = 'issue';

		parent::__construct();
	}

	/**
	 * Method to get the activity for an item.
	 *
	 * @param   integer  $id  The id of the primary key.
	 *
	 * @return  array  An array of data items on success, false on failure.
	 *
	 * @since   1.0
	 */
	public function getActivity($id)
	{
		$db    = $this->getDb();
		$query = $db->getQuery(true);

		$query->select('*');
		$query->from($db->quoteName('#__activity', 'a'));
		$query->where($db->quoteName('a.issue_id') . ' = ' . (int) $id);
		$query->order('a.created ASC');

		try
		{
			$db->setQuery($query);
			$items = $db->loadObjectList();
		}
		catch (RuntimeException $e)
		{
			JFactory::getApplication()->enqueueMessage($e->getMessage(), 'error');

			return array();
		}

		// Build the activities array
		$activities = array();
		$activities['comments'] = array();
		$activities['events']   = array();

		// Separate the event types into different area pieces
		foreach ($items as $item)
		{
			switch ($item->event)
			{
				case 'comment' :
					$activities['comments'][] = $item;
					break;

				default :
					$activities['events'][] = $item;
					break;
			}
		}

		return $activities;
	}

	/**
	 * Method to get the fields values of an issue.
	 *
	 * @param   integer  $id  The id of the primary key.
	 *
	 * @return  JRegistry  JRegistry object containing the field data.
	 *
	 * @since   1.0
	 */
	public function getFieldsData($id)
	{
		$db    = $this->getDb();
		$query = $db->getQuery(true);
		$query->select('fv.field_id, fv.value');
		$query->from($db->quoteName('#__tracker_fields_values', 'fv'));
		$query->where($db->quoteName('issue_id') . '=' . $id);

		// Join over the categories table to get the field name
		$query->select('f.title AS field_name');
		$query->join('LEFT', '#__categories AS f ON fv.field_id = f.id');

		// Join over the categories table to get the field value
		$query->select('v.title AS field_value');
		$query->join('LEFT', '#__categories AS v ON fv.value = v.id');

		try
		{
			$db->setQuery($query);
			$fields = $db->loadObjectList();
		}
		catch (RuntimeException $e)
		{
			JFactory::getApplication()->enqueueMessage($e->getMessage(), 'error');

			return array();
		}

		$arr = array();

		// Prepare the fields for display
		foreach ($fields as $field)
		{
			$arr[$field->field_id] = $field;
		}

		return $arr;
	}

	/**
	 * Method to get a single record.
	 *
	 * @param   integer  $id  The id of the primary key.
	 *
	 * @return  mixed  Object on success, false on failure.
	 *
	 * @since   1.0
	 */
	public function getItem($id)
	{
		try
		{
			$db    = $this->getDb();
			$query = $db->getQuery(true);

			$query->select('a.*');
			$query->from($db->quoteName('#__issues', 'a'));
			$query->where($db->quoteName('a.id') . ' = ' . (int) $id);

			// Join over the status table
			$query->select('s.status AS status_title, s.closed AS closed');
			$query->join('LEFT', '#__status AS s ON a.status = s.id');

			// Get the relation information
			$query->select('a1.title AS rel_title, a1.status AS rel_status');
			$query->join('LEFT', '#__issues AS a1 ON a.rel_id = a1.id');

			// Join over the status table
			$query->select('s1.closed AS rel_closed');
			$query->join('LEFT', '#__status AS s1 ON a1.status = s1.id');

			// Join over the status table
			$query->select('t.name AS rel_name');
			$query->join('LEFT', '#__issues_relations_types AS t ON a.rel_type = t.id');

			$db->setQuery($query);

			$item = $db->loadObject();

			if (!$item)
			{
				JFactory::getApplication()->enqueueMessage('Invalid project', 'error');

				return false;
			}

			$item->relations_f = $db->setQuery(
				$db->getQuery(true)
					->from($db->qn('#__issues', 'a'))
					->join('LEFT', '#__issues_relations_types AS t ON a.rel_type = t.id')
					->join('LEFT', '#__status AS s ON a.status = s.id')
					->select('a.id, a.title, a.rel_type')
					->select('t.name AS rel_name')
					->select('s.status AS status_title, s.closed AS closed')
					->where($db->quoteName('a.rel_id') . '=' . (int) $item->id)
					->order(array('a.id', 'a.rel_type'))
			)->loadObjectList();

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

			return $item;
		}
		catch (RuntimeException $e)
		{
			JFactory::getApplication()->enqueueMessage($e->getMessage(), 'error');

			return false;
		}
	}

	/**
	 * Method to save the form data.
	 *
	 * @param   array  $data  The form data.
	 *
	 * @return  boolean  True on success, False on error.
	 *
	 * @since   1.0
	 * @throws  RuntimeException
	 */
	public function save($data)
	{
		$table = $this->getTable('Issue');
		$key   = $table->getKeyName();
		$pk    = (!empty($data[$key])) ? $data[$key] : (int) $this->state->get($this->getName() . '.id');
		$isNew = true;

		// Load the row if saving an existing record.
		if ($pk > 0)
		{
			$table->load($pk);
			$isNew = false;
		}

		// Save the record
		if (!$table->save($data, false))
		{
			throw new RuntimeException('Could not save record.');
		}

		if (isset($table->$key))
		{
			$this->state->set($this->getName() . '.id', $table->$key);
		}

		$this->state->set($this->getName() . '.new', $isNew);

		return true;
	}
}
