<?php
/**
 * @package     JTracker
 * @subpackage  Table
 *
 * @copyright   Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

/**
 * Table interface class for the activity table
 *
 * @package     JTracker
 * @subpackage  Table
 * @since       1.0
 */
namespace Joomla\Tracker\Components\Tracker\Table;

use Joomla\Database\DatabaseDriver;

use Joomla\Tracker\Database\AbstractDatabaseTable;

/**
 * Class ActivitiesTable.
 *
 * @property   integer  $id
 * @property   integer  $gh_comment_id;
 * @property   integer  $issue_id;
 * @property   string   $user;
 * @property   string   $event;
 * @property   string   $text;
 * @property   string   $created;
 *
 * @package  Joomla\Tracker\Components\Tracker\Table
 *
 * @since    1.0
 */
class ActivitiesTable extends AbstractDatabaseTable
{
	/**
	 * Constructor
	 *
	 * @param   DatabaseDriver  $db  A database connector object
	 *
	 * @since   1.0
	 */
	public function __construct(DatabaseDriver $db)
	{
		parent::__construct('#__activity', 'id', $db);
	}

	/**
	 * Overloaded check function.
	 *
	 * @throws \InvalidArgumentException
	 *
	 * @since   1.0
	 * @return  boolean
	 */
	public function check()
	{
		$errors = array();

		if (trim($this->user) == '')
		{
			$errors[] = 'A user is required to be associated with an activity.';
		}

		if (trim($this->event) == '')
		{
			$errors[] = 'An event is required.';
		}

		if ($errors)
		{
			throw new \InvalidArgumentException(implode("\n", $errors));
		}

		return $this;
	}

	/**
	 * Method to store a row in the database from the JTable instance properties.
	 *
	 * If a primary key value is set the row with that primary key value will be
	 * updated with the instance property values.  If no primary key value is set
	 * a new row will be inserted into the database with the properties from the
	 * JTable instance.
	 *
	 * @param   boolean  $updateNulls  True to update fields even if they are null.
	 *
	 * @return  $this.
	 *
	 * @since   1.0
	 */
	public function store($updateNulls = false)
	{
		if (!$this->id)
		{
			// New item
			if (!$this->created)
			{
				$date = new \DateTime;
				$this->created = $date->format('Y-m-d H:i:s');
			}
		}

		return parent::store($updateNulls);
	}
}
