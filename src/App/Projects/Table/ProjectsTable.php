<?php
/**
 * Part of the Joomla Tracker's Projects Application
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Projects\Table;

use App\Groups\Table\GroupsTable;

use Joomla\Database\DatabaseDriver;

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

		if (!$this->alias)
		{
			$this->alias = $this->title;
		}

		$this->alias = $this->stringURLSafe($this->alias);

		return $this;
	}

	/**
	 * This method processes a string and replaces all accented UTF-8 characters by unaccented
	 * ASCII-7 "equivalents", whitespaces are replaced by hyphens and the string is lowercase.
	 *
	 * @param   string  $string  String to process
	 *
	 * @return  string  Processed string
	 *
	 * @since   1.0
	 */
	public static function stringURLSafe($string)
	{
		// Remove any '-' from the string since they will be used as concatenators
		$str = str_replace('-', ' ', $string);

		// $lang = Language::getInstance();
		// $str = $lang->transliterate($str);

		// Trim white spaces at beginning and end of alias and make lowercase
		$str = trim(strtolower($str));

		// Remove any duplicate whitespace, and ensure all characters are alphanumeric
		$str = preg_replace('/(\s|[^A-Za-z0-9\-])+/', '-', $str);

		// Trim dashes at beginning and end of alias
		$str = trim($str, '-');

		return $str;
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
				$data = array();
				$data['project_id'] = $newId;
				$data['title']      = 'Public';
				$data['can_view']   = 1;
				$data['can_create'] = 0;
				$data['can_edit']   = 0;
				$data['can_manage'] = 0;
				$data['system']     = 1;

				$group = new GroupsTable($this->db);
				$group->save($data);

				$data['title']      = 'User';
				$data['can_create'] = 1;

				$group = new GroupsTable($this->db);
				$group->save($data);
			}
		}

		return $this;
	}
}
