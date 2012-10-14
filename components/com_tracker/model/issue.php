<?php
/**
 * @package     BabDev.Tracker
 * @subpackage  Model
 *
 * @copyright   Copyright (C) 2012 Michael Babker. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Model to get data for the issue detail view
 *
 * @package     BabDev.Tracker
 * @subpackage  Model
 * @since       1.0
 */
class TrackerModelIssue extends JModelDatabase
{
	/**
	 * Method to get the comments for an item.
	 *
	 * @param   integer  $id  The id of the primary key.
	 *
	 * @return  mixed  An array of data items on success, false on failure.
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
			return false;
		}

		return $items;
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

		// Join over the status table
		$query->select('s.status AS status_title, s.closed AS closed');
		$query->join('LEFT', '#__status AS s ON a.status = s.id');

		$query->select(('c.title AS category'));
		$query->leftJoin('#__categories AS c ON a.catid = c.id');

		/*
		 * Join over the selects table
		 */

		// Set up the database_type column
		$query->select('f.label as database_type');
		$query->join('LEFT', '#__select_items AS f ON a.database_type = f.id');

		// Set up the web server field
		$query->select('ws.label as webserver');
		$query->join('LEFT', '#__select_items AS ws ON a.webserver = ws.id');

		// Set up php version field
		$query->select('php.label as php_version');
		$query->join('LEFT', '#__select_items AS php ON a.php_version = php.id');

		// Set up php version field
		$query->select('br.label as browser');
		$query->join('LEFT', '#__select_items AS br ON a.browser = br.id');

		try
		{
			$db->setQuery($query);
			$item = $db->loadObject();
		}
		catch (RuntimeException $e)
		{
			JFactory::getApplication()->enqueueMessage($e->getMessage(), 'error');
			return false;
		}

		$fields = $db->setQuery(
			$query->clear()
				->select('fv.field_id, fv.value')
				->from('#__tracker_fields_values AS fv')
				->where($db->qn('issue_id') . '=' . $item->id)
		)->loadObjectList();

		$arr = array();

		foreach ($fields as $field)
		{
			$arr[$field->field_id] = $field->value;
		}

		$item->fields = new JRegistry($arr);

		return $item;
	}
}
