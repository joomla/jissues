<?php
/**
 * @package     BabDev.Tracker
 * @subpackage  Table
 *
 * @copyright   Copyright (C) 2012 Michael Babker. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

/**
 * Table interface class for the issues table
 *
 * @package     BabDev.Tracker
 * @subpackage  Table
 * @since       1.0
 */
class TrackerTableIssue extends JTable
{
	/**
	 * Constructor
	 *
	 * @param   JDatabaseDriver  $db  A database connector object
	 *
	 * @since   1.0
	 */
	public function __construct(JDatabaseDriver $db)
	{
		parent::__construct('#__issues', 'id', $db);
	}

	/**
	 * Overloaded check function
	 *
	 * @return  boolean  True on success, false on failure
	 *
	 * @see     JTable::check
	 * @since   1.0
	 */
	public function check()
	{
		if (trim($this->title) == '')
		{
			$this->setError('A title is required.');
			return false;
		}

		if (trim($this->description) == '')
		{
			$this->setError('A description is required.');
			return false;
		}

		return true;
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
	 * @return  boolean  True on success.
	 *
	 * @since   1.0
	 */
	public function store($updateNulls = false)
	{
		$date = JFactory::getDate();

		if ($this->id)
		{
			// Existing item
			$this->modified = $date->toSql();
		}
		else
		{
			// New item
			if (!(int) $this->opened)
			{
				$this->opened = $date->toSql();
			}
		}
		return parent::store($updateNulls);
	}
}
