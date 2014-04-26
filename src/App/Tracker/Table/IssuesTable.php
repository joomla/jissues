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
 * @Table(name="_issues",
 *      uniqueConstraints={
 * @UniqueConstraint(name="issues_fk_rel_type",columns={"rel_type"})
 *      },
 *      indexes={
 * @Index(name="issue_number", columns={"issue_number"}),
 * @Index(name="milestone_id", columns={"milestone_id", "project_id"}),
 * @Index(name="project_id", columns={"project_id"}),
 * @Index(name="status", columns={"status"})
 *      }
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
	 * @Column(type="integer", length=11)
	 *
	 * @var  integer
	 *
	 * @since  1.0
	 */
	public $id;

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
	 * Foreign tracker id
	 *
	 * @Column(type="integer", length=11)
	 *
	 * @var  integer
	 *
	 * @since  1.0
	 */
	public $foreign_number;

	/**
	 * Project id
	 *
	 * @Column(type="integer", length=11)
	 *
	 * @var  integer
	 *
	 * @since  1.0
	 */
	public $project_id;

	/**
	 * Milestone id if applicable
	 *
	 * @Column(type="integer", length=11)
	 *
	 * @var  integer
	 *
	 * @since  1.0
	 */
	public $milestone_id;

	/**
	 * Issue title
	 *
	 * @Column(type="string", length=255)
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	public $title;

	/**
	 * Issue description
	 *
	 * @Column(type="text")
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	public $description;

	/**
	 * The raw issue description (markdown)
	 *
	 * @Column(type="text")
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	public $description_raw;

	/**
	 * Issue priority
	 *
	 * @Column(type="smallint", length=4)
	 *
	 * @var  integer
	 *
	 * @since  1.0
	 */
	public $priority;

	/**
	 * Issue status
	 *
	 * @Column(type="integer", length=11)
	 *
	 * @var  integer
	 *
	 * @since  1.0
	 */
	public $status;

	/**
	 * Issue open date
	 *
	 * @Column(type="datetime")
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	public $opened_date;

	/**
	 * Opened by username
	 *
	 * @Column(type="string", length=50)
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	public $opened_by;

	/**
	 * Issue closed date
	 *
	 * @Column(type="datetime")
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	public $closed_date;

	/**
	 * Issue closed by username
	 *
	 * @Column(type="string", length=50)
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	public $closed_by;

	/**
	 * The GitHub SHA where the issue has been closed
	 *
	 * @Column(type="string", length=40)
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	public $closed_sha;

	/**
	 * Issue modified date
	 *
	 * @Column(type="datetime")
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	public $modified_date;

	/**
	 * Issue modified by username
	 *
	 * @Column(type="string", length=50)
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	public $modified_by;

	/**
	 * Relation issue number
	 *
	 * @Column(type="integer", length=11)
	 *
	 * @var  integer
	 *
	 * @since  1.0
	 */
	public $rel_number;

	/**
	 * Relation type
	 *
	 * @Column(type="integer", length=11)
	 *
	 * @var  integer
	 *
	 * @since  1.0
	 */
	public $rel_type;

	/**
	 * If the issue has code attached - aka a pull request
	 *
	 * @Column(type="smallint", length=1)
	 *
	 * @var  integer
	 *
	 * @since  1.0
	 */
	public $has_code;

	/**
	 * Comma separated list of label IDs
	 *
	 * @Column(type="string", length=250)
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	public $labels;

	/**
	 * Build on which the issue is reported
	 *
	 * @Column(type="string", length=40)
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	public $build;

	/**
	 * Number of successful tests on an item
	 *
	 * @Column(type="smallint", length=4)
	 *
	 * @var  integer
	 *
	 * @since  1.0
	 */
	public $tests;

	/**
	 * Flag whether an item is an easy test
	 *
	 * @Column(type="smallint", length=1)
	 *
	 * @var  integer
	 *
	 * @since  1.0
	 */
	public $easy;

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
