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
class JTableIssue extends JTable
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
	 * Method to load a row from the database by primary key and bind the fields
	 * to the JTable instance properties.
	 *
	 * @param   mixed    $keys   An optional primary key value to load the row by, or an array of fields to match.  If not
	 *                           set the instance property value is used.
	 * @param   boolean  $reset  True to reset the default values before loading the new row.
	 *
	 * @return  boolean  True if successful. False if row not found.
	 *
	 * @link    http://docs.joomla.org/JTable/load
	 * @since   1.0
	 * @throws  RuntimeException
	 * @throws  UnexpectedValueException
	 */
	public function load($keys = null, $reset = true)
	{
		if (empty($keys))
		{
			// If empty, use the value of the current key
			$keyName = $this->_tbl_key;
			$keyValue = $this->$keyName;

			// If empty primary key there's is no need to load anything
			if (empty($keyValue))
			{
				return true;
			}

			$keys = array($keyName => $keyValue);
		}
		elseif (!is_array($keys))
		{
			// Load by primary key.
			$keys = array($this->_tbl_key => $keys);
		}

		if ($reset)
		{
			$this->reset();
		}

		// Initialise the query.
		$query = $this->_db->getQuery(true);
		$query->select('*');
		$query->from($this->_db->quoteName($this->_tbl, 'a'));
		$fields = array_keys($this->getProperties());

		foreach ($keys as $field => $value)
		{
			// Check that $field is in the table.
			if (!in_array($field, $fields))
			{
				throw new UnexpectedValueException(sprintf('Missing field in database: %s &#160; %s.', get_class($this), $field));
			}
			// Add the search tuple to the query.
			$query->where($this->_db->quoteName('a.' . $field) . ' = ' . $this->_db->quote($value));
		}

		// Join over the status table
		$query->select('s.status AS status_title, s.closed AS closed');
		$query->join('LEFT', '#__status AS s ON a.status = s.id');

		// Join over the selects table

		// set up the database_type column
		$query->select('f.label as database_type');
		$query->join('LEFT', '#__select_items AS f ON a.database_type = f.id');

		// set up the web server field
		$query->select('ws.label as webserver');
		$query->join('LEFT', '#__select_items AS ws ON a.webserver = ws.id');

		// set up php version field
		$query->select('php.label as php_version');
		$query->join('LEFT', '#__select_items AS php ON a.php_version = php.id');

		// set up php version field
		$query->select('br.label as browser');
		$query->join('LEFT', '#__select_items AS br ON a.browser = br.id');

		$this->_db->setQuery($query);

		$row = $this->_db->loadAssoc();

		// Check that we have a result.
		if (empty($row))
		{
			return false;
		}

		// Bind the object with the row and return.
		return $this->bind($row);
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
