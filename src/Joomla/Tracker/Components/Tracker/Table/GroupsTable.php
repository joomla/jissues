<?php
/**
 * @copyright  Copyright (C) 2013 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tracker\Components\Tracker\Table;

use Joomla\Database\DatabaseDriver;
use Joomla\Filter\InputFilter;
use Joomla\Registry\Registry;
use Joomla\Tracker\Database\AbstractDatabaseTable;

/**
 * Class GroupsTable.
 *
 * @property   integer  $group_id    group_id
 * @property   integer  $project_id  project_id
 * @property   string   $title       title
 * @property   integer  $can_view    can_view
 * @property   integer  $can_create  can_create
 * @property   integer  $can_manage  can_manage
 * @property   integer  $can_edit    can_edit
 * @property   integer  $system      system
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
	 * Method to bind an associative array or object to the AbstractDatabaseTable instance.This
	 * method only binds properties that are publicly accessible and optionally
	 * takes an array of properties to ignore when binding.
	 *
	 * @param   mixed  $source  An associative array or object to bind to the AbstractDatabaseTable instance.
	 * @param   mixed  $ignore  An optional array or space separated list of properties to ignore while binding.
	 *
	 * @throws \UnexpectedValueException
	 *
	 * @since   1.0
	 * @return GroupsTable
	 */
	public function bind($source, $ignore = array())
	{
		if (false == is_array($source))
		{
			throw new \UnexpectedValueException(__METHOD__ . ' only accepts arrays :(');
		}

		$src = new Registry($source);

		$filter = new InputFilter;

		$this->title = $filter->clean($src->get('title'));

		$this->group_id = (int) $src->get('group_id');
		$this->project_id = (int) $src->get('project_id');

		$this->can_create = $src->get('can_create') ? 1 : 0;
		$this->can_view = $src->get('can_view') ? 1 : 0;
		$this->can_edit = $src->get('can_edit') ? 1 : 0;
		$this->can_manage = $src->get('can_manage') ? 1 : 0;
		$this->system = (int) $src->get('system');

		return $this;
	}

	/**
	 * Method to perform sanity checks on the J\Table instance properties to ensure
	 * they are safe to store in the database.
	 *
	 * @throws \UnexpectedValueException
	 * @since   11.1
	 *
	 * @return  $this
	 */
	public function check()
	{
		if (!$this->title)
		{
			throw new \UnexpectedValueException('A title is required');
		}

		if (!$this->project_id)
		{
			throw new \UnexpectedValueException('No project id set');
		}

		return $this;
	}
}
