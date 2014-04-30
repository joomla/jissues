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
 * @Table(
 *    name="#__activities",
 *    indexes={
 * @Index(name="issue_number", columns={"issue_number"}),
 * @Index(name="project_id", columns={"project_id"})}
 * )
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
	 * @Column(name="activities_id", type="integer", length=11, nullable=false)
	 *
	 * @var  integer
	 *
	 * @since  1.0
	 */
	private $activitiesId;

	/**
	 * The GitHub comment id
	 *
	 * @Column(name="gh_comment_id", type="integer", length=11, nullable=true)
	 *
	 * @var  integer
	 *
	 * @since  1.0
	 */
	private $ghCommentId;

	/**
	 * THE issue number (ID)
	 *
	 * @Column(name="issue_number", type="integer", length=11, nullable=false)
	 *
	 * @var  integer
	 *
	 * @since  1.0
	 */
	private $issueNumber;

	/**
	 * The Project id
	 *
	 * @Column(name="project_id", type="integer", length=11, nullable=false)
	 *
	 * @var  integer
	 *
	 * @since  1.0
	 */
	private $projectId;

	/**
	 * The user name
	 *
	 * @Column(name="user", type="string", length=255, nullable=false)
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	private $user;

	/**
	 * The event type
	 *
	 * @Column(name="event", type="string", length=32, nullable=false)
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	private $event;

	/**
	 * The event text
	 *
	 * @Column(name="text", type="text", nullable=true)
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	private $text;

	/**
	 * The raw event text
	 *
	 * @Column(name="text_raw", type="text", nullable=true)
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	private $textRaw;

	/**
	 * created_date
	 *
	 * @Column(name="created_date", type="datetime", nullable=false)
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	private $createdDate;

	/**
	 * updated_date
	 *
	 * @Column(name="updated_date", type="datetime", nullable=false)
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	private $updatedDate;

	/**
	 * Get:  PK
	 *
	 * @return   integer
	 *
	 * @since  1.0
	 */
	public function getActivitiesId()
	{
		return $this->activitiesId;
	}

	/**
	 * Set:  PK
	 *
	 * @param   integer  $activitiesId  PK
	 *
	 * @return   $this
	 *
	 * @since  1.0
	 */
	public function setActivitiesId($activitiesId)
	{
		$this->activitiesId = $activitiesId;

		return $this;
	}

	/**
	 * Get:  The GitHub comment id
	 *
	 * @return   integer
	 *
	 * @since  1.0
	 */
	public function getGhCommentId()
	{
		return $this->ghCommentId;
	}

	/**
	 * Set:  The GitHub comment id
	 *
	 * @param   integer  $ghCommentId  The GitHub comment id
	 *
	 * @return   $this
	 *
	 * @since  1.0
	 */
	public function setGhCommentId($ghCommentId)
	{
		$this->ghCommentId = $ghCommentId;

		return $this;
	}

	/**
	 * Get:  THE issue number (ID)
	 *
	 * @return   integer
	 *
	 * @since  1.0
	 */
	public function getIssueNumber()
	{
		return $this->issueNumber;
	}

	/**
	 * Set:  THE issue number (ID)
	 *
	 * @param   integer  $issueNumber  THE issue number (ID)
	 *
	 * @return   $this
	 *
	 * @since  1.0
	 */
	public function setIssueNumber($issueNumber)
	{
		$this->issueNumber = $issueNumber;

		return $this;
	}

	/**
	 * Get:  The Project id
	 *
	 * @return   integer
	 *
	 * @since  1.0
	 */
	public function getProjectId()
	{
		return $this->projectId;
	}

	/**
	 * Set:  The Project id
	 *
	 * @param   integer  $projectId  The Project id
	 *
	 * @return   $this
	 *
	 * @since  1.0
	 */
	public function setProjectId($projectId)
	{
		$this->projectId = $projectId;

		return $this;
	}

	/**
	 * Get:  The user name
	 *
	 * @return   string
	 *
	 * @since  1.0
	 */
	public function getUser()
	{
		return $this->user;
	}

	/**
	 * Set:  The user name
	 *
	 * @param   string  $user  The user name
	 *
	 * @return   $this
	 *
	 * @since  1.0
	 */
	public function setUser($user)
	{
		$this->user = $user;

		return $this;
	}

	/**
	 * Get:  The event type
	 *
	 * @return   string
	 *
	 * @since  1.0
	 */
	public function getEvent()
	{
		return $this->event;
	}

	/**
	 * Set:  The event type
	 *
	 * @param   string  $event  The event type
	 *
	 * @return   $this
	 *
	 * @since  1.0
	 */
	public function setEvent($event)
	{
		$this->event = $event;

		return $this;
	}

	/**
	 * Get:  The event text
	 *
	 * @return   string
	 *
	 * @since  1.0
	 */
	public function getText()
	{
		return $this->text;
	}

	/**
	 * Set:  The event text
	 *
	 * @param   string  $text  The event text
	 *
	 * @return   $this
	 *
	 * @since  1.0
	 */
	public function setText($text)
	{
		$this->text = $text;

		return $this;
	}

	/**
	 * Get:  The raw event text
	 *
	 * @return   string
	 *
	 * @since  1.0
	 */
	public function getTextRaw()
	{
		return $this->textRaw;
	}

	/**
	 * Set:  The raw event text
	 *
	 * @param   string  $textRaw  The raw event text
	 *
	 * @return   $this
	 *
	 * @since  1.0
	 */
	public function setTextRaw($textRaw)
	{
		$this->textRaw = $textRaw;

		return $this;
	}

	/**
	 * Get:  created_date
	 *
	 * @return   string
	 *
	 * @since  1.0
	 */
	public function getCreatedDate()
	{
		return $this->createdDate;
	}

	/**
	 * Set:  created_date
	 *
	 * @param   string  $createdDate  created_date
	 *
	 * @return   $this
	 *
	 * @since  1.0
	 */
	public function setCreatedDate($createdDate)
	{
		$this->createdDate = $createdDate;

		return $this;
	}

	/**
	 * Get:  updated_date
	 *
	 * @return   string
	 *
	 * @since  1.0
	 */
	public function getUpdatedDate()
	{
		return $this->updatedDate;
	}

	/**
	 * Set:  updated_date
	 *
	 * @param   string  $updatedDate  updated_date
	 *
	 * @return   $this
	 *
	 * @since  1.0
	 */
	public function setUpdatedDate($updatedDate)
	{
		$this->updatedDate = $updatedDate;

		return $this;
	}

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
