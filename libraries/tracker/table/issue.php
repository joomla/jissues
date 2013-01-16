<?php
/**
 * @package     JTracker
 * @subpackage  Table
 *
 * @copyright   Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

/**
 * Table interface class for the issues table
 *
 * @package     JTracker
 * @subpackage  Table
 * @since       1.0
 */
class JTableIssue extends JTable
{
	/**
	 * Internal array of field values.
	 *
	 * @var array
	 */
	protected $fieldValues = array();

	/**
	 * Constructor
	 *
	 * @param   JDatabaseDriver  $db  A database connector object
	 *
	 * @since   1.0
	 */
	public function __construct(JDatabaseDriver $db)
	{
		parent::__construct('#__issues', 'id', $db);
	}

	/**
	 * Method to compute the default name of the asset.
	 * The default name is in the form table_name.id
	 * where id is the value of the primary key of the table.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	protected function _getAssetName()
	{
		return 'com_tracker.issue.' . (int) $this->{$this->_tbl_key};
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
	 * @throws  InvalidArgumentException
	 */
	public function bind($src, $ignore = array())
	{
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
		elseif ($src instanceof JInput)
		{
			$data     = new stdClass;
			$data->id = $src->get('id');

			$this->fieldValues = $this->_cleanFields($src->get('fields', array(), 'array'));

			return parent::bind($data, $ignore);
		}

		throw new InvalidArgumentException(sprintf('%1$s can not bind to %2$s', __METHOD__, gettype($src)));
	}

	/**
	 * Method to return the title to use for the asset table.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	protected function _getAssetTitle()
	{
		return $this->title;
	}

	/**
	 * Method to get the parent asset id for the record
	 *
	 * @param   JTable   $table  A JTable object (optional) for the asset parent
	 * @param   integer  $id     The id (optional) of the content.
	 *
	 * @return  integer
	 *
	 * @since   1.0
	 */
	protected function _getAssetParentId(JTable $table = null, $id = null)
	{
		$assetId = null;

		// Issues are nested directly underneath the component.
		if ($assetId === null)
		{
			// Build the query to get the asset id for the component.
			$query = $this->_db->getQuery(true);
			$query->select($this->_db->quoteName('id'));
			$query->from($this->_db->quoteName('#__assets'));
			$query->where($this->_db->quoteName('name') . ' = ' . $this->_db->quote('com_tracker'));

			// Get the asset id from the database.
			$this->_db->setQuery($query);

			if ($result = $this->_db->loadResult())
			{
				$assetId = (int) $result;
			}
		}

		// Return the asset id.
		if ($assetId)
		{
			return $assetId;
		}
		else
		{
			return parent::_getAssetParentId($table, $id);
		}
	}

	/**
	 * Overloaded check function
	 *
	 * @return  boolean  True on success, false on failure
	 *
	 * @see     JTable::check
	 * @since   1.0
	 */
	public function check()
	{
		$app = JFactory::getApplication();
		$valid = true;

		if (trim($this->title) == '')
		{
			$app->enqueueMessage('A title is required.', 'error');

			$valid = false;
		}

		if (trim($this->description) == '')
		{
			$app->enqueueMessage('A description is required.', 'error');

			$valid =  false;
		}

		return $valid;
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
	 * @throws  RuntimeException
	 */
	public function store($updateNulls = false)
	{
		$date = JFactory::getDate();

		$isNew = ($this->id < 1);

		if (!$isNew)
		{
			// Existing item
			$this->modified = JFactory::getDate()->toSql();
		}
		else
		{
			// New item
			if (!(int) $this->opened)
			{
				$this->opened = JFactory::getDate()->toSql();
			}
		}

		// Execute the parent store method
		if (!parent::store($updateNulls))
		{
			throw new RuntimeException($this->getError());
		}

		$db = $this->getDbo();
		$query = $db->getQuery(true);

		// Add a record to the activity table if a new item
		// TODO: Remove the check for CLI once moved to live instance
		if ($isNew && JFactory::getApplication()->get('cli_app') != true)
		{
			$columnsArray = array(
				$db->quoteName('issue_id'),
				$db->quoteName('user'),
				$db->quoteName('event'),
				$db->quoteName('created')
			);

			$query->insert($db->quoteName('#__activity'));
			$query->columns($columnsArray);
			$query->values(
				(int) $this->id . ', '
				. $db->quote(JFactory::getUser()->username) . ', '
				. $db->quote('open') . ', '
				. $db->quote($this->opened)
			);
			$db->setQuery($query);
			$db->execute();
		}

		// If we don't have the extra fields, return here
		if (!(isset($this->fieldValues)) || !$this->fieldValues)
		if (!$this->fieldValues)
		{
			return true;
		}

		// Store the extra fields.
		$db = $this->_db;

		$issueId = ($this->id)
			// Existing issue
			? $this->id
			// New issue - ugly..
			: $this->_db->setQuery(
				$this->_db->getQuery(true)
					->from($this->_tbl)
					->select('MAX(' . $this->_tbl_key . ')')
			)->loadResult();

		// Check the tracker table to see if the extra fields are already present

		$ids = $db->setQuery($db->getQuery(true)
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
					$db->setQuery($queryUpdate->clear('set')->clear('where')
						->set($db->qn('value') . '=' . $db->q($v))
						->where($db->qn('issue_id') . '=' . (int) $issueId)
						->where($db->qn('field_id') . '=' . (int) $k))
						->execute();
				}
				else
				{
					// New item
					$db->setQuery($queryInsert->clear('values')
						->values(implode(', ', array(
							(int) $issueId,
							(int) $k,
							$db->q($v)
						))))
						->execute();
				}
			}
		}

		return true;
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
		$filter = JFilterInput::getInstance();

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
