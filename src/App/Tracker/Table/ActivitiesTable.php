<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012-2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Table;

use Joomla\Database\DatabaseDriver;

use JTracker\Database\AbstractDatabaseTable;

/**
 * Table interface class for the #__activities table
 *
 * @property   integer  $activities_id  PK
 * @property   integer  $gh_comment_id  The GitHub comment id
 * @property   integer  $issue_number   THE issue number (ID)
 * @property   integer  $project_id     The Project id
 * @property   string   $user           The user name
 * @property   string   $event          The event type
 * @property   string   $text           The event text
 * @property   string   $text_raw       The raw event text
 * @property   string   $created_date   created_date
 * @property   string   $updated_date   updated_date
 *
 * @since  1.0
 */
class ActivitiesTable extends AbstractDatabaseTable
{
	/**
	 * Constructor
	 *
	 * @param   DatabaseDriver  $database  A database connector object.
	 *
	 * @since   1.0
	 */
	public function __construct(DatabaseDriver $database)
	{
		parent::__construct('#__activities', 'activities_id', $database);
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
		$errors = [];

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
				$this->created_date = (new \DateTime)->format($this->db->getDateFormat());
			}
		}

		return parent::store($updateNulls);
	}
}
