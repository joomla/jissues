<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Tracker\Table;

use Joomla\Database\DatabaseDriver;

use JTracker\Database\AbstractDatabaseTable;

/**
 * Table interface class for the #__activities table
 *
 * @property   integer  $activities_id  PK
 * @property   integer  $gh_comment_id  The GitHub comment id
 * @property   integer  $issue_number   THE issue number
 * @property   integer  $project_id     The Project id
 * @property   string   $user           The user name
 * @property   string   $event          The event type
 * @property   string   $text           The event text
 * @property   string   $text_raw       The raw  event text
 * @property   string   $created_date   created_date
 *
 * @since  1.0
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
		parent::__construct('#__activities', 'activities_id', $db);
	}

	/**
	 * Method to perform sanity checks on the AbstractDatabaseTable instance properties to ensure
	 * they are safe to store in the database.
	 *
	 * @return  $this  Method allows chaining
	 *
	 * @since   1.0
	 * @throws  \InvalidArgumentException
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
	 * Method to store a row in the database from the AbstractDatabaseTable instance properties.
	 *
	 * If a primary key value is set the row with that primary key value will be
	 * updated with the instance property values.  If no primary key value is set
	 * a new row will be inserted into the database with the properties from the
	 * AbstractDatabaseTable instance.
	 *
	 * @param   boolean  $updateNulls  True to update fields even if they are null.
	 *
	 * @return  ActivitiesTable
	 *
	 * @since   1.0
	 */
	public function store($updateNulls = false)
	{
		if (!$this->activities_id)
		{
			// New item
			if (!$this->created_date)
			{
				$date = new \DateTime;
				$this->created_date = $date->format('Y-m-d H:i:s');
			}
		}

		return parent::store($updateNulls);
	}
}
