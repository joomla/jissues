<?php
/**
 * @package     X
 * @subpackage  X.Y
 *
 * @copyright   Copyright (C) 2012 X. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

/**
 * Issues table class.
 *
 * @package  X
 *
 * @since    1.0
 */
class TrackerTableIssues extends JTable
{
	protected $fieldValues = array();

	/**
	 * Constructor.
	 *
	 * @param   JDatabaseDriver  $db  Database driver object.
	 *
	 * @since   1.0
	 */
	public function __construct(JDatabaseDriver $db = null)
	{
		$db = $db ? : JFactory::getDbo();

		parent::__construct('#__issues', 'id', $db);
	}

	/**
	 * Method to provide a shortcut to binding, checking and storing a JTable
	 * instance to the database table.  The method will check a row in once the
	 * data has been stored and if an ordering filter is present will attempt to
	 * reorder the table rows based on the filter.  The ordering filter is an instance
	 * property name.  The rows that will be reordered are those whose value matches
	 * the JTable instance for the property specified.
	 *
	 * @param   mixed   $src             An associative array or object to bind to the JTable instance.
	 * @param   string  $orderingFilter  Filter for the order updating
	 * @param   mixed   $ignore          An optional array or space separated list of properties
	 *                                   to ignore while binding.
	 *
	 * @throws RuntimeException
	 *
	 * @return  boolean  True on success.
	 *
	 * @link    http://docs.joomla.org/JTable/save
	 * @since   11.1
	 */
	public function save($src, $orderingFilter = '', $ignore = '')
	{
		if (false == parent::save($src, $orderingFilter, $ignore))
			throw new RuntimeException(__METHOD__ . ' - Error while saving the table');

		return $this;
	}

	/**
	 * Method to bind an associative array or object to the JTable instance.This
	 * method only binds properties that are publicly accessible and optionally
	 * takes an array of properties to ignore when binding.
	 *
	 * @param   mixed  $src     An associative array or object to bind to the JTable instance.
	 * @param   mixed  $ignore  An optional array or space separated list of properties to ignore while binding.
	 *
	 * @throws RuntimeException
	 * @return  boolean  True on success.
	 *
	 * @link    http://docs.joomla.org/JTable/bind
	 * @since   11.1
	 */
	public function bind($src, $ignore = array())
	{
		if (is_array($src))
		{
			return parent::bind($src, $ignore);
		}
		elseif ($src instanceof JInput)
		{
			$data = new stdClass;

			$data->id = $src->get('id');

			$fields = $src->get('fields', array(), 'array');

			JArrayHelper::toInteger($fields);

			if (isset($fields['catid']))
			{
				$data->catid = $fields['catid'];

				unset($fields['catid']);
			}

			$this->fieldValues = $fields;

			return parent::bind($data, $ignore);
		}

		throw new RuntimeException(__METHOD__ . ' - Invalid source');
	}

	/**
	 * Method to store a row in the database from the JTable instance properties.
	 * If a primary key value is set the row with that primary key value will be
	 * updated with the instance property values.  If no primary key value is set
	 * a new row will be inserted into the database with the properties from the
	 * JTable instance.
	 *
	 * @param   boolean  $updateNulls  True to update fields even if they are null.
	 *
	 * @throws RuntimeException
	 *
	 * @return  boolean  True on success.
	 *
	 * @link    http://docs.joomla.org/JTable/store
	 * @since   11.1
	 */
	public function store($updateNulls = false)
	{
		if (false == parent::store($updateNulls))
			throw new RuntimeException($this->getError());

		if (!$this->fieldValues)
			return $this;

		$db = $this->_db;

		$ids = $db->setQuery(
			$db->getQuery(true)
				->select('fv.field_id')
				->from('#__tracker_fields_values AS fv')
				->where($db->qn('fv.issue_id') . '=' . (int) $this->id)
		)->loadColumn();

		$queryInsert = $this->_db->getQuery(true)
			->insert($this->_db->qn('#__tracker_fields_values'))
			->columns('issue_id, field_id, value');

		$queryUpdate = $this->_db->getQuery(true)
			->update($this->_db->qn('#__tracker_fields_values'));

		foreach ($this->fieldValues as $k => $v)
		{
			if (in_array($k, $ids))
			{
				// Update item
				$db->setQuery(
					$queryUpdate->clear('set')->clear('where')
						->set($db->qn('value') . '=' . (int) $v)
						->where($db->qn('issue_id') . '=' . (int) $this->id)
						->where($db->qn('field_id') . '=' . (int) $k)
				)->execute();
			}
			else
			{
				// New item
				$db->setQuery(
					$queryInsert->clear('values')
						->values(implode(', ', array((int) $this->id, (int) $k, (int) $v)))
				)->execute();
			}
		}

		return $this;
	}

}
