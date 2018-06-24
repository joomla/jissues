<?php
/**
 * Part of the Joomla Tracker's Groups Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Groups\Table;

use Joomla\Database\DatabaseDriver;
use Joomla\Filter\InputFilter;
use Joomla\Registry\Registry;

use JTracker\Database\AbstractDatabaseTable;

/**
 * Class GroupsTable.
 *
 * @property   integer  $group_id     group_id
 * @property   integer  $project_id   project_id
 * @property   string   $title        title
 * @property   integer  $can_view     can_view
 * @property   integer  $can_create   can_create
 * @property   integer  $can_manage   can_manage
 * @property   integer  $can_edit     can_edit
 * @property   integer  $can_editown  can_editown
 * @property   integer  $system       system
 *
 * @since  1.0
 */
class GroupsTable extends AbstractDatabaseTable
{
	/**
	 * Constructor
	 *
	 * @param   DatabaseDriver  $database  A database connector object
	 *
	 * @since   1.0
	 */
	public function __construct(DatabaseDriver $database)
	{
		parent::__construct('#__accessgroups', 'group_id', $database);
	}

	/**
	 * Method to bind an associative array or object to the AbstractDatabaseTable instance.  This
	 * method only binds properties that are publicly accessible and optionally
	 * takes an array of properties to ignore when binding.
	 *
	 * @param   mixed  $source  An associative array or object to bind to the AbstractDatabaseTable instance.
	 * @param   mixed  $ignore  An optional array or space separated list of properties to ignore while binding.
	 *
	 * @return  $this  Method allows chaining
	 *
	 * @since   1.0
	 * @throws  \UnexpectedValueException
	 */
	public function bind($source, $ignore = [])
	{
		if (false === is_array($source))
		{
			throw new \UnexpectedValueException(__METHOD__ . ' only accepts arrays :(');
		}

		$src = new Registry($source);

		$this->title = (new InputFilter)->clean($src->get('title'));

		$this->group_id   = (int) $src->get('group_id');
		$this->project_id = (int) $src->get('project_id');
		$this->system     = (int) $src->get('system');

		// The following values come in as checkboxes ¡: "ON" or not set.
		$this->can_view    = $src->get('can_view') ? 1 : 0;
		$this->can_create  = $src->get('can_create') ? 1 : 0;
		$this->can_edit    = $src->get('can_edit') ? 1 : 0;
		$this->can_editown = $src->get('can_editown') ? 1 : 0;
		$this->can_manage  = $src->get('can_manage') ? 1 : 0;

		return $this;
	}

	/**
	 * Method to perform sanity checks on the AbstractDatabaseTable instance properties to ensure
	 * they are safe to store in the database.
	 *
	 * @return  $this  Method allows chaining
	 *
	 * @since   1.0
	 * @throws  \UnexpectedValueException
	 */
	public function check()
	{
		if (!$this->title)
		{
			throw new \UnexpectedValueException(g11n3t('A title is required'));
		}

		if (!$this->project_id)
		{
			throw new \UnexpectedValueException('No project id set');
		}

		return $this;
	}

	/**
	 * Method to delete a row from the database table by primary key value.
	 *
	 * @param   mixed  $pKey  An optional primary key value to delete.  If not set the instance property value is used.
	 *
	 * @return  AbstractDatabaseTable
	 *
	 * @since   1.0
	 * @throws  \UnexpectedValueException
	 */
	public function delete($pKey = null)
	{
		parent::delete($pKey);

		// Delete the entries in the map table.
		$this->db->setQuery(
			$this->db->getQuery(true)
				->delete($this->db->quoteName('#__user_accessgroup_map'))
				->where($this->db->quoteName('group_id') . ' = ' . (int) $this->group_id)
		)->execute();

		return $this;
	}
}
