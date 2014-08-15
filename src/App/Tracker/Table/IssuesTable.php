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
 * Table interface class for the #__issues table
 *
 * @property   integer  $id               PK
 * @property   integer  $issue_number     THE issue number (ID)
 * @property   integer  $foreign_number   Foreign tracker id
 * @property   integer  $project_id       Project id
 * @property   integer  $milestone_id     Milestone id if applicable
 * @property   string   $title            Issue title
 * @property   string   $description      Issue description
 * @property   string   $description_raw  The raw issue description (markdown)
 * @property   integer  $priority         Issue priority
 * @property   integer  $status           Issue status
 * @property   string   $opened_date      Issue open date
 * @property   string   $opened_by        Opened by username
 * @property   string   $closed_date      Issue closed date
 * @property   string   $closed_by        Issue closed by username
 * @property   string   $closed_sha       The GitHub SHA where the issue has been closed
 * @property   string   $modified_date    Issue modified date
 * @property   string   $modified_by      Issue modified by username
 * @property   integer  $rel_number       Relation number
 * @property   string   $rel_type         Relation type
 * @property   integer  $has_code         If the issue has code attached - aka a pull request.
 * @property   string   $labels           Comma separated list of label IDs
 * @property   integer  $vote_id          Vote id
 * @property   integer  $build            Build the issue is reported on
 * @property   integer  $tests            Number of successful tests
 * @property   integer  $easy             Flag if item is an easy test
 * @property   string   $merge_state      The merge state
 * @property   string   $gh_merge_status  The GitHub merge status (JSON encoded)
 *
 * @since  1.0
 */
class IssuesTable extends AbstractDatabaseTable
{
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
			if ($this->modified_date == '0000-00-00 00:00:00')
			{
				$this->modified_date = $date;
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

					case 'description' :

						// Do nothing

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
}
