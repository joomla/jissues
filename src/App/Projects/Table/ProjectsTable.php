<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Projects\Table;

use App\Groups\Table\GroupsTable;

use Joomla\Database\DatabaseDriver;
use Joomla\Filter\InputFilter;
use Joomla\Filter\OutputFilter;

use JTracker\Database\AbstractDatabaseTable;

/**
 * Table interface class for the #__tracker_projects table
 *
 * @property   integer  $project_id        PK
 * @property   string   $title             Project title
 * @property   string   $alias             Project URL alias
 * @property   string   $gh_user           GitHub user
 * @property   string   $gh_project        GitHub project
 * @property   string   $ext_tracker_link  A tracker link format (e.g. http://tracker.com/issue/%d)
 *
 * @since  1.0
 */
class ProjectsTable extends AbstractDatabaseTable
{
	/**
	 * Constructor
	 *
	 * @param   DatabaseDriver  $db  A database connector object
	 *
	 * @since   1.0
	 */
	public function __construct(DatabaseDriver $db)
	{
		parent::__construct('#__tracker_projects', 'project_id', $db);
	}

	/**
	 * Method to perform sanity checks on the J\Table instance properties to ensure
	 * they are safe to store in the database.
	 *
	 * @throws \UnexpectedValueException
	 * @since   1.0
	 *
	 * @return  ProjectsTable
	 */
	public function check()
	{
		if (!$this->title)
		{
			throw new \UnexpectedValueException('A title is required');
		}

		if (!$this->alias)
		{
			$this->alias = $this->title;
		}

		$this->alias = OutputFilter::stringURLSafe($this->alias);

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
	 * @link    http://docs.joomla.org/JTable/store
	 * @since   11.1
	 */
	public function store($updateNulls = false)
	{
		$oldId = $this->{$this->getKeyName()};

		parent::store($updateNulls);

		if (!$oldId)
		{
			// New item - Create default access groups.

			$newId = $this->{$this->getKeyName()};

			if ($newId)
			{
				$group = new GroupsTable($this->db);

				$group->project_id = $newId;
				$group->title = 'Public';
				$group->can_view = 1;
				$group->can_create = 0;
				$group->can_edit = 0;
				$group->can_manage = 0;
				$group->system = 1;

				$group->store();

				$group->group_id = null;
				$group->title = 'User';
				$group->can_create = 1;

				$group->store();
			}
		}

		return $this;
	}
}
