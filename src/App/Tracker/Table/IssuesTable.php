<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Tracker\Table;

use Joomla\Database\DatabaseDriver;
use Joomla\Input\Input;
use Joomla\Filter\InputFilter;
use Joomla\Date\Date;
use Joomla\Factory;

use JTracker\Database\AbstractDatabaseTable;
use Joomla\Utilities\ArrayHelper;

/**
 * Table interface class for the #__issues table
 *
 * @property   integer  $id               PK
 * @property   integer  $issue_number     THE issue number (ID)
 * @property   integer  $foreign_number   Foreign tracker id
 * @property   integer  $project_id       Project id
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
 * @property   integer  $rel_id           Relation id user
 * @property   string   $rel_type         Relation type
 * @property   integer  $has_code         If the issue has code attached - aka a pull request.

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
	protected $oldObject;

	/**
	 * Constructor
	 *
	 * @param   DatabaseDriver  $db  A database connector object
	 *
	 * @since   1.0
	 */
	public function __construct(DatabaseDriver $db)
	{
		parent::__construct('#__issues', 'id', $db);
	}

	/**
	 * Method to bind an associative array or object to the AbstractDatabaseTable instance.  This
	 * method only binds properties that are publicly accessible and optionally
	 * takes an array of properties to ignore when binding.
	 *
	 * @param   mixed  $src     An associative array or object to bind to the AbstractDatabaseTable instance.
	 * @param   mixed  $ignore  An optional array or space separated list of properties to ignore while binding.
	 *
	 * @return  IssuesTable
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
	 * Overloaded check function
	 *
	 * @return  IssuesTable
	 *
	 * @since   1.0
	 * @throws  \InvalidArgumentException
	 */
	public function check()
	{
		$errors = array();

		if (trim($this->title) == '')
		{
			$errors[] = 'A title is required.';
		}

		if (trim($this->description) == '')
		{
			$errors[] = 'A description is required.';
		}

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
	 * @return  IssuesTable
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	public function store($updateNulls = false)
	{
		/* @type \JTracker\Application\TrackerApplication $application */
		$application = Factory::$application;

		$isNew = ($this->id < 1);
		$date  = new Date;
		$date  = $date->format($this->db->getDateFormat());

		if (!$isNew)
		{
			// Existing item
			$this->modified_date = $date;

			$this->modified_by = $application->getUser()->username;
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

		// TODO: Remove the check for CLI once moved to live instance
		// TODO 2) This has been deactivated. every action should perform a proper entry in the activities table.
		if (0)
		{
			// $isNew && $application->get('cli_app') != true)
			// Add a record to the activity table if a new item
			$table = new ActivitiesTable($this->db);

			$table->event = 'open';
			$table->created_date = $this->opened_date;
			$table->user = $application->getUser()->username;
			$table->issue_number = (int) $this->issue_number;
			$table->project_id = (int) $this->project_id;

			$table->store();
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
	 * @since  1.0
	 * @return $this
	 */
	private function processChanges()
	{
		/* @type \JTracker\Application\TrackerApplication $application */
		$application = Factory::$application;

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
			$date = new Date;

			$data = array(
				$this->db->quoteName('issue_number') => (int) $this->issue_number,
				$this->db->quoteName('user')         => $this->db->quote($application->getUser()->username),
				$this->db->quoteName('project_id')   => (int) $this->project_id,
				$this->db->quoteName('event')        => $this->db->quote('change'),
				$this->db->quoteName('text')         => $this->db->quote(json_encode($changes)),
				$this->db->quoteName('created_date') => $this->db->quote($date->format('Y-m-d H:i:s'))
			);

			$this->db->setQuery(
				$this->db->getQuery(true)
					->insert($this->db->quoteName('#__activities'))
					->columns(array_keys($data))
					->values(implode(',', $data))
			)->execute();
		}

		return $this;
	}

	/**
	 * Process extra fields.
	 *
	 * @since  1.0
	 * @return $this
	 */
	private function processFields()
	{
		// Store the extra fields.
		$db = $this->db;

		$issueId = ($this->id)
			// Existing issue
			? $this->id
			// New issue - ugly..
			: $this->db->setQuery(
				$this->db->getQuery(true)
					->from($this->tableName)
					->select('MAX(' . $this->getKeyName() . ')')
			)->loadResult();

		// Check the tracker table to see if the extra fields are already present

		$ids = $db->setQuery(
			$db->getQuery(true)
				->select('fv.field_id')
				->from('#__tracker_fields_values AS fv')
				->where($db->qn('fv.issue_id') . '=' . (int) $issueId)
		)->loadColumn();

		$queryInsert = $db->getQuery(true)
			->insert($db->qn('#__tracker_fields_values'))
			->columns('issue_id, field_id, value');

		$queryUpdate = $db->getQuery(true)
			->update($db->qn('#__tracker_fields_values'));

		foreach ($this->fieldValues as $fields)
		{
			foreach ($fields as $k => $v)
			{
				if (in_array($k, $ids))
				{
					// Update item
					$db->setQuery(
						$queryUpdate->clear('set')->clear('where')
							->set($db->qn('value') . '=' . $db->q($v))
							->where($db->qn('issue_id') . '=' . (int) $issueId)
							->where($db->qn('field_id') . '=' . (int) $k)
					)->execute();
				}
				else
				{
					// New item
					$db->setQuery(
						$queryInsert->clear('values')
							->values(
								implode(', ', array(
										(int) $issueId,
										(int) $k,
										$db->q($v)
									)
								)
							)
					)->execute();
				}
			}
		}

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
