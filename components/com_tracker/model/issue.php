<?php
/**
 * @package     JTracker
 * @subpackage  com_tracker
 *
 * @copyright   Copyright (C) 2012 Open Source Matters. All rights reserved.
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
class TrackerModelIssue extends JModelTrackerform
{
	/**
	 * Instantiate the model.
	 *
	 * @since  1.0
	 */
	public function __construct()
	{
		parent::__construct();

		// Load the JTable object
		$this->table = JTable::getInstance('Issue');
	}

	/**
	 * Method to get the comments for an item.
	 *
	 * @param   integer  $id  The id of the primary key.
	 *
	 * @return  array  An array of data items on success, false on failure.
	 *
	 * @since   1.0
	 */
	public function getComments($id)
	{
		$db = $this->getDb();
		$query = $db->getQuery(true);

		$query->select('*');
		$query->from($db->quoteName('#__issue_comments', 'a'));
		$query->where($db->quoteName('a.issue_id') . ' = ' . (int) $id);

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

		return $items;
	}

	/**
	 * Method to get the comments for an item.
	 *
	 * @param   integer  $id  The id of the primary key.
	 *
	 * @return  JRegistry  JRegistry object containing the field data.
	 *
	 * @since   1.0
	 */
	public function getFields($id)
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
			return false;
		}

		$arr = array();

		// Prepare the fields for display
		foreach ($fields as $field)
		{
			$name  = strtolower(str_replace(' ', '_', $field->field_name));
			$value = strtolower(str_replace(' ', '_', $field->field_value));
			$arr[$name] = $value;
		}

		$item = new JRegistry($arr);

		return $item;
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
		$db = $this->getDb();
		$query = $db->getQuery(true);

		$query->select('a.*');
		$query->from($db->quoteName('#__issues', 'a'));
		$query->where($db->quoteName('a.id') . ' = ' . (int) $id);

		// Join over the category table to get the project title
		$query->select(('c.title AS category'));
		$query->leftJoin('#__categories AS c ON a.catid = c.id');

		// Join over the status table
		$query->select('s.status AS status_title, s.closed AS closed');
		$query->join('LEFT', '#__status AS s ON a.status = s.id');

		try
		{
			$db->setQuery($query);
			return $db->loadObject();
		}
		catch (RuntimeException $e)
		{
			JFactory::getApplication()->enqueueMessage($e->getMessage(), 'error');
			return false;
		}
	}
}
