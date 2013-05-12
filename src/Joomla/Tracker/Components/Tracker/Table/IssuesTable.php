<?php
/**
 * @package     JTracker
 * @subpackage  Table
 *
 * @copyright   Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tracker\Components\Tracker\Table;

use Joomla\Database\DatabaseDriver;
use Joomla\Input\Input;
use Joomla\Filter\InputFilter;
use Joomla\Date\Date;
use Joomla\Factory;

use Joomla\Tracker\Database\AbstractDatabaseTable;
use Psr\Log\InvalidArgumentException;

/**
 * Class IssuesTable.
 *
 * @package  Joomla\Tracker\Components\Tracker\Table
 *
 * @property   integer  $id           PK.
 * @property   string   $title        Issue title.
 * @property   string   $description  Issue description.
 * @property   integer  $gh_id        GitHub id.
 *
 * @since    1.0
 */
class IssuesTable extends AbstractDatabaseTable
{
	/**
	 * Internal array of field values.
	 *
	 * @var array
	 */
	protected $fieldValues = array();

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
	 * Method to bind an associative array or object to the JTable instance.This
	 * method only binds properties that are publicly accessible and optionally
	 * takes an array of properties to ignore when binding.
	 *
	 * @param   mixed  $src     An associative array or object to bind to the JTable instance.
	 * @param   mixed  $ignore  An optional array or space separated list of properties to ignore while binding.
	 *
	 * @return  boolean  True on success.
	 *
	 * @link    http://docs.joomla.org/JTable/bind
	 * @since   1.0
	 * @throws  \InvalidArgumentException
	 */
	public function bind($src, $ignore = array())
	{
		if ($this->id)
		{
			// Store the table to compute the differences later.
			$this->oldObject = clone($this);
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
	 * @throws \Psr\Log\InvalidArgumentException
	 * @since   1.0
	 *
	 * @return  $this
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
			throw new InvalidArgumentException(implode("\n", $errors));
		}

		return $this;
	}

	/**
	 * Method to store a row in the database from the JTable instance properties.
	 * If a primary key value is set the row with that primary key value will be
	 * updated with the instance property values.  If no primary key value is set
	 * a new row will be inserted into the database with the properties from the
	 * JTable instance.
	 *
	 * @param   boolean  $updateNulls  True to update fields even if they are null.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	public function store($updateNulls = false)
	{
		$isNew = ($this->id < 1);
		$date  = new Date;
		$date  = $date->format('Y-m-d H:i:s');

		if (!$isNew)
		{
			// Existing item
			$this->modified = $date;

			// Factory::getUser()->id;
			$this->modified_by = 0;
		}
		else
		{
			// New item
			if (!(int) $this->opened)
			{
				$this->opened = $date;
			}
		}

		// Execute the parent store method
		parent::store($updateNulls);

		/*
		 * Post-Save Actions
		 */

		$query = $this->db->getQuery(true);

		// Add a record to the activity table if a new item
		// TODO: Remove the check for CLI once moved to live instance
		if ($isNew)// && JFactory::getApplication()->get('cli_app') != true)
		{
			$columnsArray = array(
				$this->db->quoteName('issue_id'),
				$this->db->quoteName('user'),
				$this->db->quoteName('event'),
				$this->db->quoteName('created')
			);

			$query->insert($this->db->quoteName('#__activity'));
			$query->columns($columnsArray);
			$query->values(
				(int) $this->id . ', '
					// . $this->db->quote(JFactory::getUser()->username) . ', '
					. $this->db->quote('') . ', '
					. $this->db->quote('open') . ', '
					. $this->db->quote($this->opened)
			);

			$this->db->setQuery($query);
			$this->db->execute();
		}

		// Add a record to the activities table including the changes made to an item.
		if ($this->oldObject)
		{
			// Compute the changes
			$changes = array();

			foreach ($this->getFields() as $fName => $field)
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
						case 'modified' :
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
					$this->db->quoteName('issue_id') => (int) $this->id,
					// $this->db->quoteName('user')     => $db->quote(JFactory::getUser()->username),
					$this->db->quoteName('user')     => $this->db->quote(''),
					$this->db->quoteName('event')    => $this->db->quote('change'),
					$this->db->quoteName('text')     => $this->db->quote(json_encode($changes)),
					$this->db->quoteName('created')  => $this->db->quote($date->format('Y-m-d H:i:s'))
				);

				$this->db->setQuery(
					$query->clear()
						->insert($this->db->quoteName('#__activity'))
						->columns(array_keys($data))
						->values(implode(',', $data))
				)->execute();
			}
		}

		// If we don't have the extra fields, return here
		if (!$this->fieldValues)
		{
			return true;
		}

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
	 * @return array
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
