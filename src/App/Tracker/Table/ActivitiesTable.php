<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Table;

use Joomla\Database\DatabaseDriver;

use JTracker\Database\AbstractDatabaseTable;

/**
 * Table interface class for the "activities" database table.
 *
 * @Entity
 * @Table(name="_activities")
 *
 * @since  1.0
 */
class ActivitiesTable extends AbstractDatabaseTable
{
	/**
	 * PK
	 *
	 * @Id
	 * @GeneratedValue
	 * @Column(type="integer", length=11)
	 *
	 * @var  integer
	 *
	 * @since  1.0
	 */
	public $activities_id;

	/**
	 * The GitHub comment id
	 *
	 * @Column(type="integer", length=11)
	 *
	 * @var  integer
	 *
	 * @since  1.0
	 */
	public $gh_comment_id;

	/**
	 * THE issue number (ID)
	 *
	 * @Column(type="integer", length=11)
	 *
	 * @var  integer
	 *
	 * @since  1.0
	 */
	public $issue_number;

	/**
	 * The Project id
	 *
	 * @Column(type="integer", length=11)
	 *
	 * @var  integer
	 *
	 * @since  1.0
	 */
	public $project_id;

	/**
	 * The user name
	 *
	 * @Column(type="string", length=255)
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	public $user;

	/**
	 * The event type
	 *
	 * @Column(type="string", length=32)
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	public $event;

	/**
	 * The event text
	 *
	 * @Column(type="text")
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	public $text;

	/**
	 * The raw event text
	 *
	 * @Column(type="text")
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	public $text_raw;

	/**
	 * created_date
	 *
	 * @Column(type="datetime")
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	public $created_date;

	/**
	 * updated_date
	 *
	 * @Column(type="datetime")
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	public $updated_date;

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
