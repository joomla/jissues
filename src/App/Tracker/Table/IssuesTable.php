<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Table;

use Joomla\Database\DatabaseDriver;
use Joomla\Input\Input;
use Joomla\Filter\InputFilter;
use Joomla\Date\Date;
use Joomla\Utilities\ArrayHelper;

use JTracker\Authentication\GitHub\GitHubUser;
use JTracker\Database\AbstractDatabaseTable;

/**
 * Table interface class for the "issues" database table.
 *
 * @Entity
 *
 * @Table(
 *    name="#__issues",
 *    indexes={
 * @Index(name="status", columns={"status"}),
 * @Index(name="issue_number", columns={"issue_number"}),
 * @Index(name="project_id", columns={"project_id"}),
 * @Index(name="milestone_id", columns={"milestone_id", "project_id"}),
 * @Index(name="issues_fk_rel_type", columns={"rel_type"})
 *    }
 * )
 *
 * @since  1.0
 */
class IssuesTable extends AbstractDatabaseTable
{
	/**
	 * PK
	 *
	 * @Id
	 * @GeneratedValue
	 * @Column(name="id", type="integer", length=11, nullable=false)
	 *
	 * @var  integer
	 *
	 * @since  1.0
	 */
	private $id;

	/**
	 * THE issue number (ID)
	 *
	 * @Column(name="issue_number", type="integer", length=11, nullable=true)
	 *
	 * @var  integer
	 *
	 * @since  1.0
	 */
	private $issueNumber;

	/**
	 * Foreign tracker id
	 *
	 * @Column(name="foreign_number", type="integer", length=11, nullable=true)
	 *
	 * @var  integer
	 *
	 * @since  1.0
	 */
	private $foreignNumber;

	/**
	 * Project id
	 *
	 * @Column(name="project_id", type="integer", length=11, nullable=true)
	 *
	 * @var  integer
	 *
	 * @since  1.0
	 */
	private $projectId;

	/**
	 * Milestone id if applicable
	 *
	 * @Column(name="milestone_id", type="integer", length=11, nullable=true)
	 *
	 * @var  integer
	 *
	 * @since  1.0
	 */
	private $milestoneId;

	/**
	 * Issue title
	 *
	 * @Column(name="title", type="string", length=255, nullable=false)
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	private $title;

	/**
	 * Issue description
	 *
	 * @Column(name="description", type="text", nullable=false)
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	private $description;

	/**
	 * The raw issue description (markdown)
	 *
	 * @Column(name="description_raw", type="text", nullable=false)
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	private $descriptionRaw;

	/**
	 * Issue priority
	 *
	 * @Column(name="priority", type="smallint", length=4, nullable=false)
	 *
	 * @var  integer
	 *
	 * @since  1.0
	 */
	private $priority;

	/**
	 * Issue status
	 *
	 * @Column(name="status", type="integer", length=11, nullable=false)
	 *
	 * @var  integer
	 *
	 * @since  1.0
	 */
	private $status;

	/**
	 * Issue open date
	 *
	 * @Column(name="opened_date", type="datetime", nullable=false)
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	private $openedDate;

	/**
	 * Opened by username
	 *
	 * @Column(name="opened_by", type="string", length=50, nullable=true)
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	private $openedBy;

	/**
	 * Issue closed date
	 *
	 * @Column(name="closed_date", type="datetime", nullable=false)
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	private $closedDate;

	/**
	 * Issue closed by username
	 *
	 * @Column(name="closed_by", type="string", length=50, nullable=true)
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	private $closedBy;

	/**
	 * The GitHub SHA where the issue has been closed
	 *
	 * @Column(name="closed_sha", type="string", length=40, nullable=true)
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	private $closedSha;

	/**
	 * Issue modified date
	 *
	 * @Column(name="modified_date", type="datetime", nullable=false)
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	private $modifiedDate;

	/**
	 * Issue modified by username
	 *
	 * @Column(name="modified_by", type="string", length=50, nullable=true)
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	private $modifiedBy;

	/**
	 * Relation issue number
	 *
	 * @Column(name="rel_number", type="integer", length=11, nullable=true)
	 *
	 * @var  integer
	 *
	 * @since  1.0
	 */
	private $relNumber;

	/**
	 * Relation type
	 *
	 * @Column(name="rel_type", type="integer", length=11, nullable=true)
	 *
	 * @var  integer
	 *
	 * @since  1.0
	 */
	private $relType;

	/**
	 * If the issue has code attached - aka a pull request
	 *
	 * @Column(name="has_code", type="smallint", length=1, nullable=false)
	 *
	 * @var  integer
	 *
	 * @since  1.0
	 */
	private $hasCode;

	/**
	 * Comma separated list of label IDs
	 *
	 * @Column(name="labels", type="string", length=250, nullable=false)
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	private $labels;

	/**
	 * Build on which the issue is reported
	 *
	 * @Column(name="build", type="string", length=40, nullable=false)
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	private $build;

	/**
	 * Number of successful tests on an item
	 *
	 * @Column(name="tests", type="smallint", length=4, nullable=false)
	 *
	 * @var  integer
	 *
	 * @since  1.0
	 */
	private $tests;

	/**
	 * Flag whether an item is an easy test
	 *
	 * @Column(name="easy", type="smallint", length=1, nullable=false)
	 *
	 * @var  integer
	 *
	 * @since  1.0
	 */
	private $easy;

	/**
	 * Get:  PK
	 *
	 * @return   integer
	 *
	 * @since  1.0
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * Set:  PK
	 *
	 * @param   integer  $id  PK
	 *
	 * @return   $this
	 *
	 * @since  1.0
	 */
	public function setId($id)
	{
		$this->id = $id;

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
	 * Get:  Foreign tracker id
	 *
	 * @return   integer
	 *
	 * @since  1.0
	 */
	public function getForeignNumber()
	{
		return $this->foreignNumber;
	}

	/**
	 * Set:  Foreign tracker id
	 *
	 * @param   integer  $foreignNumber  Foreign tracker id
	 *
	 * @return   $this
	 *
	 * @since  1.0
	 */
	public function setForeignNumber($foreignNumber)
	{
		$this->foreignNumber = $foreignNumber;

		return $this;
	}

	/**
	 * Get:  Project id
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
	 * Set:  Project id
	 *
	 * @param   integer  $projectId  Project id
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
	 * Get:  Milestone id if applicable
	 *
	 * @return   integer
	 *
	 * @since  1.0
	 */
	public function getMilestoneId()
	{
		return $this->milestoneId;
	}

	/**
	 * Set:  Milestone id if applicable
	 *
	 * @param   integer  $milestoneId  Milestone id if applicable
	 *
	 * @return   $this
	 *
	 * @since  1.0
	 */
	public function setMilestoneId($milestoneId)
	{
		$this->milestoneId = $milestoneId;

		return $this;
	}

	/**
	 * Get:  Issue title
	 *
	 * @return   string
	 *
	 * @since  1.0
	 */
	public function getTitle()
	{
		return $this->title;
	}

	/**
	 * Set:  Issue title
	 *
	 * @param   string  $title  Issue title
	 *
	 * @return   $this
	 *
	 * @since  1.0
	 */
	public function setTitle($title)
	{
		$this->title = $title;

		return $this;
	}

	/**
	 * Get:  Issue description
	 *
	 * @return   string
	 *
	 * @since  1.0
	 */
	public function getDescription()
	{
		return $this->description;
	}

	/**
	 * Set:  Issue description
	 *
	 * @param   string  $description  Issue description
	 *
	 * @return   $this
	 *
	 * @since  1.0
	 */
	public function setDescription($description)
	{
		$this->description = $description;

		return $this;
	}

	/**
	 * Get:  The raw issue description (markdown)
	 *
	 * @return   string
	 *
	 * @since  1.0
	 */
	public function getDescriptionRaw()
	{
		return $this->descriptionRaw;
	}

	/**
	 * Set:  The raw issue description (markdown)
	 *
	 * @param   string  $descriptionRaw  The raw issue description (markdown)
	 *
	 * @return   $this
	 *
	 * @since  1.0
	 */
	public function setDescriptionRaw($descriptionRaw)
	{
		$this->descriptionRaw = $descriptionRaw;

		return $this;
	}

	/**
	 * Get:  Issue priority
	 *
	 * @return   integer
	 *
	 * @since  1.0
	 */
	public function getPriority()
	{
		return $this->priority;
	}

	/**
	 * Set:  Issue priority
	 *
	 * @param   integer  $priority  Issue priority
	 *
	 * @return   $this
	 *
	 * @since  1.0
	 */
	public function setPriority($priority)
	{
		$this->priority = $priority;

		return $this;
	}

	/**
	 * Get:  Issue status
	 *
	 * @return   integer
	 *
	 * @since  1.0
	 */
	public function getStatus()
	{
		return $this->status;
	}

	/**
	 * Set:  Issue status
	 *
	 * @param   integer  $status  Issue status
	 *
	 * @return   $this
	 *
	 * @since  1.0
	 */
	public function setStatus($status)
	{
		$this->status = $status;

		return $this;
	}

	/**
	 * Get:  Issue open date
	 *
	 * @return   string
	 *
	 * @since  1.0
	 */
	public function getOpenedDate()
	{
		return $this->openedDate;
	}

	/**
	 * Set:  Issue open date
	 *
	 * @param   string  $openedDate  Issue open date
	 *
	 * @return   $this
	 *
	 * @since  1.0
	 */
	public function setOpenedDate($openedDate)
	{
		$this->openedDate = $openedDate;

		return $this;
	}

	/**
	 * Get:  Opened by username
	 *
	 * @return   string
	 *
	 * @since  1.0
	 */
	public function getOpenedBy()
	{
		return $this->openedBy;
	}

	/**
	 * Set:  Opened by username
	 *
	 * @param   string  $openedBy  Opened by username
	 *
	 * @return   $this
	 *
	 * @since  1.0
	 */
	public function setOpenedBy($openedBy)
	{
		$this->openedBy = $openedBy;

		return $this;
	}

	/**
	 * Get:  Issue closed date
	 *
	 * @return   string
	 *
	 * @since  1.0
	 */
	public function getClosedDate()
	{
		return $this->closedDate;
	}

	/**
	 * Set:  Issue closed date
	 *
	 * @param   string  $closedDate  Issue closed date
	 *
	 * @return   $this
	 *
	 * @since  1.0
	 */
	public function setClosedDate($closedDate)
	{
		$this->closedDate = $closedDate;

		return $this;
	}

	/**
	 * Get:  Issue closed by username
	 *
	 * @return   string
	 *
	 * @since  1.0
	 */
	public function getClosedBy()
	{
		return $this->closedBy;
	}

	/**
	 * Set:  Issue closed by username
	 *
	 * @param   string  $closedBy  Issue closed by username
	 *
	 * @return   $this
	 *
	 * @since  1.0
	 */
	public function setClosedBy($closedBy)
	{
		$this->closedBy = $closedBy;

		return $this;
	}

	/**
	 * Get:  The GitHub SHA where the issue has been closed
	 *
	 * @return   string
	 *
	 * @since  1.0
	 */
	public function getClosedSha()
	{
		return $this->closedSha;
	}

	/**
	 * Set:  The GitHub SHA where the issue has been closed
	 *
	 * @param   string  $closedSha  The GitHub SHA where the issue has been closed
	 *
	 * @return   $this
	 *
	 * @since  1.0
	 */
	public function setClosedSha($closedSha)
	{
		$this->closedSha = $closedSha;

		return $this;
	}

	/**
	 * Get:  Issue modified date
	 *
	 * @return   string
	 *
	 * @since  1.0
	 */
	public function getModifiedDate()
	{
		return $this->modifiedDate;
	}

	/**
	 * Set:  Issue modified date
	 *
	 * @param   string  $modifiedDate  Issue modified date
	 *
	 * @return   $this
	 *
	 * @since  1.0
	 */
	public function setModifiedDate($modifiedDate)
	{
		$this->modifiedDate = $modifiedDate;

		return $this;
	}

	/**
	 * Get:  Issue modified by username
	 *
	 * @return   string
	 *
	 * @since  1.0
	 */
	public function getModifiedBy()
	{
		return $this->modifiedBy;
	}

	/**
	 * Set:  Issue modified by username
	 *
	 * @param   string  $modifiedBy  Issue modified by username
	 *
	 * @return   $this
	 *
	 * @since  1.0
	 */
	public function setModifiedBy($modifiedBy)
	{
		$this->modifiedBy = $modifiedBy;

		return $this;
	}

	/**
	 * Get:  Relation issue number
	 *
	 * @return   integer
	 *
	 * @since  1.0
	 */
	public function getRelNumber()
	{
		return $this->relNumber;
	}

	/**
	 * Set:  Relation issue number
	 *
	 * @param   integer  $relNumber  Relation issue number
	 *
	 * @return   $this
	 *
	 * @since  1.0
	 */
	public function setRelNumber($relNumber)
	{
		$this->relNumber = $relNumber;

		return $this;
	}

	/**
	 * Get:  Relation type
	 *
	 * @return   integer
	 *
	 * @since  1.0
	 */
	public function getRelType()
	{
		return $this->relType;
	}

	/**
	 * Set:  Relation type
	 *
	 * @param   integer  $relType  Relation type
	 *
	 * @return   $this
	 *
	 * @since  1.0
	 */
	public function setRelType($relType)
	{
		$this->relType = $relType;

		return $this;
	}

	/**
	 * Get:  If the issue has code attached - aka a pull request
	 *
	 * @return   integer
	 *
	 * @since  1.0
	 */
	public function getHasCode()
	{
		return $this->hasCode;
	}

	/**
	 * Set:  If the issue has code attached - aka a pull request
	 *
	 * @param   integer  $hasCode  If the issue has code attached - aka a pull request
	 *
	 * @return   $this
	 *
	 * @since  1.0
	 */
	public function setHasCode($hasCode)
	{
		$this->hasCode = $hasCode;

		return $this;
	}

	/**
	 * Get:  Comma separated list of label IDs
	 *
	 * @return   string
	 *
	 * @since  1.0
	 */
	public function getLabels()
	{
		return $this->labels;
	}

	/**
	 * Set:  Comma separated list of label IDs
	 *
	 * @param   string  $labels  Comma separated list of label IDs
	 *
	 * @return   $this
	 *
	 * @since  1.0
	 */
	public function setLabels($labels)
	{
		$this->labels = $labels;

		return $this;
	}

	/**
	 * Get:  Build on which the issue is reported
	 *
	 * @return   string
	 *
	 * @since  1.0
	 */
	public function getBuild()
	{
		return $this->build;
	}

	/**
	 * Set:  Build on which the issue is reported
	 *
	 * @param   string  $build  Build on which the issue is reported
	 *
	 * @return   $this
	 *
	 * @since  1.0
	 */
	public function setBuild($build)
	{
		$this->build = $build;

		return $this;
	}

	/**
	 * Get:  Number of successful tests on an item
	 *
	 * @return   integer
	 *
	 * @since  1.0
	 */
	public function getTests()
	{
		return $this->tests;
	}

	/**
	 * Set:  Number of successful tests on an item
	 *
	 * @param   integer  $tests  Number of successful tests on an item
	 *
	 * @return   $this
	 *
	 * @since  1.0
	 */
	public function setTests($tests)
	{
		$this->tests = $tests;

		return $this;
	}

	/**
	 * Get:  Flag whether an item is an easy test
	 *
	 * @return   integer
	 *
	 * @since  1.0
	 */
	public function getEasy()
	{
		return $this->easy;
	}

	/**
	 * Set:  Flag whether an item is an easy test
	 *
	 * @param   integer  $easy  Flag whether an item is an easy test
	 *
	 * @return   $this
	 *
	 * @since  1.0
	 */
	public function setEasy($easy)
	{
		$this->easy = $easy;

		return $this;
	}

	/**
	 * Internal array of field values.
	 *
	 * @var    array
	 * @since  1.0
	 */
	protected $fieldValues = array();

	/**
	 * Container for an IssuesTable object to compare differences
	 *
	 * @var    IssuesTable
	 * @since  1.0
	 */
	protected $oldObject = null;

	/**
	 * User object
	 *
	 * @var    GitHubUser
	 * @since  1.0
	 */
	protected $user = null;

	/**
	 * Constructor
	 *
	 * @param   DatabaseDriver  $database  A database connector object.
	 *
	 * @since   1.0
	 */
	public function __construct(DatabaseDriver $database)
	{
		parent::__construct('#__issues', 'id', $database);
	}

	/**
	 * Method to bind an associative array or object to the AbstractDatabaseTable instance.  This
	 * method only binds properties that are publicly accessible and optionally
	 * takes an array of properties to ignore when binding.
	 *
	 * @param   mixed  $src     An associative array or object to bind to the AbstractDatabaseTable instance.
	 * @param   mixed  $ignore  An optional array or space separated list of properties to ignore while binding.
	 *
	 * @return  $this  Method allows chaining
	 *
	 * @since   1.0
	 * @throws  \InvalidArgumentException
	 */
	public function bind($src, $ignore = array())
	{
		if ($this->id)
		{
			$oldValues = array();

			foreach ($this as $k => $v)
			{
				$oldValues[$k] = $v;
			}

			// Store the old values to compute the differences later.
			$this->oldObject = ArrayHelper::toObject($oldValues);
		}

		if (is_array($src))
		{
			if (isset($src['fields']))
			{
				// "Save" the field values and store them later.
				$this->fieldValues = $this->_cleanFields($src['fields']);

				unset($src['fields']);
			}

			return parent::bind($src, $ignore);
		}
		elseif ($src instanceof Input)
		{
			$data     = new \stdClass;
			$data->id = $src->get('id');

			$this->fieldValues = $this->_cleanFields($src->get('fields', array(), 'array'));

			return parent::bind($data, $ignore);
		}

		throw new \InvalidArgumentException(sprintf('%1$s can not bind to %2$s', __METHOD__, gettype($src)));
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

		$this->title = trim($this->title);

		if ($this->title == '')
		{
			$errors[] = g11n3t('A title is required.');
		}
		elseif (strlen($this->title) > 255)
		{
			$errors[] = g11n3t('The title max length is 255 chars.');
		}

		if (trim($this->build) == '')
		{
			$errors[] = g11n3t('A build is required.');
		}
		elseif (strlen($this->build) > 40)
		{
			$errors[] = g11n3t('A build max length is 40 chars.');
		}

		// Commented for now because many GitHub requests are received without a description

		/*if (trim($this->description) == '')
		{
			$errors[] = 'A description is required.';
		}*/

		if ($errors)
		{
			throw new \InvalidArgumentException(implode("\n", $errors));
		}

		return $this;
	}

	/**
	 * Method to store a row in the database from the AbstractDatabaseTable instance properties.
	 * If a primary key value is set the row with that primary key value will be
	 * updated with the instance property values.  If no primary key value is set
	 * a new row will be inserted into the database with the properties from the
	 * AbstractDatabaseTable instance.
	 *
	 * @param   boolean  $updateNulls  True to update fields even if they are null.
	 *
	 * @return  $this  Method allows chaining
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	public function store($updateNulls = false)
	{
		$isNew = ($this->id < 1);
		$date  = new Date;
		$date  = $date->format($this->db->getDateFormat());

		if (!$isNew)
		{
			// Existing item
			if (!$this->modified_date)
			{
				$this->modified_date = $date;
			}

			if (!$this->modified_by)
			{
				$this->modified_by = $this->getUser()->username;
			}
		}
		else
		{
			// New item
			if (!(int) $this->opened_date)
			{
				$this->opened_date = $date;
			}
		}

		// Execute the parent store method
		parent::store($updateNulls);

		/*
		 * Post-Save Actions
		 */

		// Add a record to the activity table if a new item
		if ($isNew)
		{
			$data = array();
			$data['event']        = 'open';
			$data['created_date'] = $this->opened_date;
			$data['user']         = $this->opened_by;
			$data['issue_number'] = (int) $this->issue_number;
			$data['project_id']   = (int) $this->project_id;

			$table = new ActivitiesTable($this->db);
			$table->save($data);
		}

		if ($this->oldObject)
		{
			// Add a record to the activities table including the changes made to an item.
			$this->processChanges();
		}

		if ($this->fieldValues)
		{
			// If we have extra fields, process them.
			$this->processFields();
		}

		return $this;
	}

	/**
	 * Compute the changes.
	 *
	 * @return  $this  Method allows chaining
	 *
	 * @since   1.0
	 */
	private function processChanges()
	{
		$changes = array();

		foreach ($this as $fName => $field)
		{
			if (!$this->$fName && !$this->oldObject->$fName)
			{
				// Both values are "empty"
				continue;
			}

			if ($this->$fName != $this->oldObject->$fName)
			{
				$change = new \stdClass;

				$change->name = $fName;
				$change->old  = $this->oldObject->$fName;
				$change->new  = $this->$fName;

				switch ($fName)
				{
					case 'modified_date' :
					case 'modified_by' :
						// Expected change ;)
						break;

					case 'description_raw' :
						// @todo do something ?
						$changes[] = $change;

						break;

					default :
						$changes[] = $change;
						break;
				}
			}
		}

		if ($changes)
		{
			$data = array();
			$data['event']        = 'change';
			$data['created_date'] = $this->modified_date;
			$data['user']         = $this->modified_by;
			$data['issue_number'] = (int) $this->issue_number;
			$data['project_id']   = (int) $this->project_id;
			$data['text']         = json_encode($changes);

			$table = new ActivitiesTable($this->db);
			$table->save($data);
		}

		return $this;
	}

	/**
	 * Process extra fields.
	 *
	 * @return  $this  Method allows chaining
	 *
	 * @since   1.0
	 */
	private function processFields()
	{
		return $this;
	}

	/**
	 * Clean the field values.
	 *
	 * @param   array  $fields  The field array.
	 *
	 * @return  array  Container with cleaned fields
	 *
	 * @since   1.0
	 */
	private function _cleanFields(array $fields)
	{
		$filter = new InputFilter;

		// Selects are integers.
		foreach (array_keys($fields['selects']) as $key)
		{
			if (!$fields['selects'][$key])
			{
				unset($fields['selects'][$key]);
			}
			else
			{
				$fields['selects'][$key] = (int) $fields['selects'][$key];
			}
		}

		// Textfields are strings.
		foreach (array_keys($fields['textfields']) as $key)
		{
			if (!$fields['textfields'][$key])
			{
				unset($fields['textfields'][$key]);
			}
			else
			{
				$fields['textfields'][$key] = $filter->clean($fields['textfields'][$key]);
			}
		}

		// Checkboxes are selected if they are present.
		foreach (array_keys($fields['checkboxes']) as $key)
		{
			if (!$fields['checkboxes'][$key])
			{
				unset($fields['checkboxes'][$key]);
			}
			else
			{
				$fields['checkboxes'][$key] = 1;
			}
		}

		return $fields;
	}

	/**
	 * Get the user.
	 *
	 * @return  GitHubUser
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	public function getUser()
	{
		if (is_null($this->user))
		{
			throw new \RuntimeException('User not set');
		}

		return $this->user;
	}

	/**
	 * Set the user.
	 *
	 * @param   GitHubUser  $user  The user.
	 *
	 * @return  $this  Method allows chaining
	 *
	 * @since   1.0
	 */
	public function setUser(GitHubUser $user)
	{
		$this->user = $user;

		return $this;
	}
}
