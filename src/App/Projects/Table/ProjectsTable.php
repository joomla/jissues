<?php
/**
 * Part of the Joomla Tracker's Projects Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Projects\Table;

use App\Groups\Table\GroupsTable;

use Joomla\Database\DatabaseDriver;

use JTracker\Database\AbstractDatabaseTable;

/**
 * Table interface class for the "tracker_projects" database table.
 *
 * @Entity
 * @Table(
 *    name="#__tracker_projects",
 *    indexes={
 * @Index(name="alias", columns={"alias"})
 *    }
 * )
 *
 * @since  1.0
 */
class ProjectsTable extends AbstractDatabaseTable
{
	/**
	 * PK
	 *
	 * @Id
	 * @GeneratedValue
	 * @Column(name="project_id", type="integer", length=11, nullable=false)
	 *
	 * @var  integer
	 *
	 * @since  1.0
	 */
	private $projectId;

	/**
	 * Project title
	 *
	 * @Column(name="title", type="string", length=150, nullable=false)
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	private $title;

	/**
	 * Project URL alias
	 *
	 * @Column(name="alias", type="string", length=150, nullable=false)
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	private $alias;

	/**
	 * GitHub user
	 *
	 * @Column(name="gh_user", type="string", length=150, nullable=false)
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	private $ghUser;

	/**
	 * GitHub project
	 *
	 * @Column(name="gh_project", type="string", length=150, nullable=false)
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	private $ghProject;

	/**
	 * A tracker link format (e.g. http://tracker.com/issue/%d)
	 *
	 * @Column(name="ext_tracker_link", type="string", length=500, nullable=false)
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	private $extTrackerLink;

	/**
	 * Project short title
	 *
	 * @Column(name="short_title", type="string", length=50, nullable=false)
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	private $shortTitle;

	/**
	 * Get:  PK
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
	 * Set:  PK
	 *
	 * @param   integer  $projectId  PK
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
	 * Get:  Project title
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
	 * Set:  Project title
	 *
	 * @param   string  $title  Project title
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
	 * Get:  Project URL alias
	 *
	 * @return   string
	 *
	 * @since  1.0
	 */
	public function getAlias()
	{
		return $this->alias;
	}

	/**
	 * Set:  Project URL alias
	 *
	 * @param   string  $alias  Project URL alias
	 *
	 * @return   $this
	 *
	 * @since  1.0
	 */
	public function setAlias($alias)
	{
		$this->alias = $alias;

		return $this;
	}

	/**
	 * Get:  GitHub user
	 *
	 * @return   string
	 *
	 * @since  1.0
	 */
	public function getGhUser()
	{
		return $this->ghUser;
	}

	/**
	 * Set:  GitHub user
	 *
	 * @param   string  $ghUser  GitHub user
	 *
	 * @return   $this
	 *
	 * @since  1.0
	 */
	public function setGhUser($ghUser)
	{
		$this->ghUser = $ghUser;

		return $this;
	}

	/**
	 * Get:  GitHub project
	 *
	 * @return   string
	 *
	 * @since  1.0
	 */
	public function getGhProject()
	{
		return $this->ghProject;
	}

	/**
	 * Set:  GitHub project
	 *
	 * @param   string  $ghProject  GitHub project
	 *
	 * @return   $this
	 *
	 * @since  1.0
	 */
	public function setGhProject($ghProject)
	{
		$this->ghProject = $ghProject;

		return $this;
	}

	/**
	 * Get:  A tracker link format (e.g. http://tracker.com/issue/%d)
	 *
	 * @return   string
	 *
	 * @since  1.0
	 */
	public function getExtTrackerLink()
	{
		return $this->extTrackerLink;
	}

	/**
	 * Set:  A tracker link format (e.g. http://tracker.com/issue/%d)
	 *
	 * @param   string  $extTrackerLink  A tracker link format (e.g. http://tracker.com/issue/%d)
	 *
	 * @return   $this
	 *
	 * @since  1.0
	 */
	public function setExtTrackerLink($extTrackerLink)
	{
		$this->extTrackerLink = $extTrackerLink;

		return $this;
	}

	/**
	 * Get:  Project short title
	 *
	 * @return   string
	 *
	 * @since  1.0
	 */
	public function getShortTitle()
	{
		return $this->shortTitle;
	}

	/**
	 * Set:  Project short title
	 *
	 * @param   string  $shortTitle  Project short title
	 *
	 * @return   $this
	 *
	 * @since  1.0
	 */
	public function setShortTitle($shortTitle)
	{
		$this->shortTitle = $shortTitle;

		return $this;
	}

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

		if (!$this->short_title)
		{
			throw new \UnexpectedValueException(g11n3t('A short title is required'));
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
