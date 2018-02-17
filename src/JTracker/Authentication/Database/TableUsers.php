<?php
/**
 * Part of the Joomla Tracker Authentication Package
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Authentication\Database;

use Joomla\Database\DatabaseDriver;

use JTracker\Database\AbstractDatabaseTable;

/**
 * Table class for interfacing with the #__users table
 *
 * @property   integer  $id             PK
 * @property   string   $name           The users name
 * @property   string   $username       The users username
 * @property   string   $email          The users e-mail
 * @property   integer  $block          If the user is blocked
 * @property   integer  $sendEmail      If the users receives e-mail
 * @property   string   $registerDate   The register date
 * @property   string   $lastvisitDate  The last visit date
 * @property   string   $params         Parameters
 *
 * @since  1.0
 */
class TableUsers extends AbstractDatabaseTable
{
	/**
	 * Constructor.
	 *
	 * @param   DatabaseDriver  $database  A database connector object.
	 *
	 * @since   1.0
	 */
	public function __construct(DatabaseDriver $database)
	{
		parent::__construct('#__users', 'id', $database);
	}

	/**
	 * Load data by a given user name.
	 *
	 * @param   string  $userName  The user name
	 *
	 * @return  $this  Method allows chaining
	 *
	 * @since   1.0
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

	/**
	 * Method to perform sanity checks on the AbstractDatabaseTable instance properties to ensure
	 * they are safe to store in the database.  Child classes should override this
	 * method to make sure the data they are storing in the database is safe and
	 * as expected before storage.
	 *
	 * @return  $this  Method allows chaining
	 *
	 * @since   1.0
	 */
	public function check()
	{
		if ($this->params instanceof \Joomla\Registry\Registry)
		{
			$this->params = $this->params->toString();
		}

		return $this;
	}
}
