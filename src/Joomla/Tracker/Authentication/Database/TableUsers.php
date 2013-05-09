<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tracker\Authentication\Database;

use Joomla\Database\DatabaseDriver;

use Joomla\Tracker\Database\AbstractDatabaseTable;

/**
 * Class TableUsers.
 *
 * @property integer $id Primary key
 *
 * @since  1.0
 */
class TableUsers extends AbstractDatabaseTable
{
	/**
	 * Constructor.
	 *
	 * @param   DatabaseDriver  $db  A database connector object
	 *
	 * @since   1.0
	 */
	public function __construct(DatabaseDriver $db)
	{
		parent::__construct('#__users', 'id', $db);
	}

	/**
	 * Load data by a given user name.
	 *
	 * @param   string  $userName  The user name
	 *
	 * @return TableUsers
	 */
	public function loadByUserName($userName)
	{
		$check = $this->db->setQuery(
			$this->db->getQuery(true)
				->select('*')
				->from($this->tableName)
				->where($this->db->quoteName('username') . ' = ' . $this->db->quote($userName))
		)->loadObject();

		return ($check) ? $this->bind($check) : $this;
	}
}
