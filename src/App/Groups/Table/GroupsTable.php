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
 * Table interface class for the "accessgroups" database table.
 *
 * @Entity
 * @Table(name="#__accessgroups")
 *
 * @since  1.0
 */
class GroupsTable extends AbstractDatabaseTable
{
	/**
	 * group_id
	 *
	 * @Id
	 * @GeneratedValue
	 * @Column(name="group_id", type="integer", length=11, nullable=false)
	 *
	 * @var  integer
	 *
	 * @since  1.0
	 */
	private $groupId;

	/**
	 * project_id
	 *
	 * @Column(name="project_id", type="integer", length=11, nullable=false)
	 *
	 * @var  integer
	 *
	 * @since  1.0
	 */
	private $projectId;

	/**
	 * title
	 *
	 * @Column(name="title", type="string", length=150, nullable=false)
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	private $title;

	/**
	 * can_view
	 *
	 * @Column(name="can_view", type="integer", length=11, nullable=false)
	 *
	 * @var  integer
	 *
	 * @since  1.0
	 */
	private $canView;

	/**
	 * can_create
	 *
	 * @Column(name="can_create", type="integer", length=11, nullable=false)
	 *
	 * @var  integer
	 *
	 * @since  1.0
	 */
	private $canCreate;

	/**
	 * can_manage
	 *
	 * @Column(name="can_manage", type="integer", length=11, nullable=false)
	 *
	 * @var  integer
	 *
	 * @since  1.0
	 */
	private $canManage;

	/**
	 * can_edit
	 *
	 * @Column(name="can_edit", type="integer", length=11, nullable=false)
	 *
	 * @var  integer
	 *
	 * @since  1.0
	 */
	private $canEdit;

	/**
	 * can_editown
	 *
	 * @Column(name="can_editown", type="integer", length=11, nullable=false)
	 *
	 * @var  integer
	 *
	 * @since  1.0
	 */
	private $canEditown;

	/**
	 * system
	 *
	 * @Column(name="system", type="integer", length=11, nullable=false)
	 *
	 * @var  integer
	 *
	 * @since  1.0
	 */
	private $system;

	/**
	 * Get:  group_id
	 *
	 * @return   integer
	 *
	 * @since  1.0
	 */
	public function getGroupId()
	{
		return $this->groupId;
	}

	/**
	 * Set:  group_id
	 *
	 * @param   integer  $groupId  group_id
	 *
	 * @return   $this
	 *
	 * @since  1.0
	 */
	public function setGroupId($groupId)
	{
		$this->groupId = $groupId;

		return $this;
	}

	/**
	 * Get:  project_id
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
	 * Set:  project_id
	 *
	 * @param   integer  $projectId  project_id
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
	 * Get:  title
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
	 * Set:  title
	 *
	 * @param   string  $title  title
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
	 * Get:  can_view
	 *
	 * @return   integer
	 *
	 * @since  1.0
	 */
	public function getCanView()
	{
		return $this->canView;
	}

	/**
	 * Set:  can_view
	 *
	 * @param   integer  $canView  can_view
	 *
	 * @return   $this
	 *
	 * @since  1.0
	 */
	public function setCanView($canView)
	{
		$this->canView = $canView;

		return $this;
	}

	/**
	 * Get:  can_create
	 *
	 * @return   integer
	 *
	 * @since  1.0
	 */
	public function getCanCreate()
	{
		return $this->canCreate;
	}

	/**
	 * Set:  can_create
	 *
	 * @param   integer  $canCreate  can_create
	 *
	 * @return   $this
	 *
	 * @since  1.0
	 */
	public function setCanCreate($canCreate)
	{
		$this->canCreate = $canCreate;

		return $this;
	}

	/**
	 * Get:  can_manage
	 *
	 * @return   integer
	 *
	 * @since  1.0
	 */
	public function getCanManage()
	{
		return $this->canManage;
	}

	/**
	 * Set:  can_manage
	 *
	 * @param   integer  $canManage  can_manage
	 *
	 * @return   $this
	 *
	 * @since  1.0
	 */
	public function setCanManage($canManage)
	{
		$this->canManage = $canManage;

		return $this;
	}

	/**
	 * Get:  can_edit
	 *
	 * @return   integer
	 *
	 * @since  1.0
	 */
	public function getCanEdit()
	{
		return $this->canEdit;
	}

	/**
	 * Set:  can_edit
	 *
	 * @param   integer  $canEdit  can_edit
	 *
	 * @return   $this
	 *
	 * @since  1.0
	 */
	public function setCanEdit($canEdit)
	{
		$this->canEdit = $canEdit;

		return $this;
	}

	/**
	 * Get:  can_editown
	 *
	 * @return   integer
	 *
	 * @since  1.0
	 */
	public function getCanEditown()
	{
		return $this->canEditown;
	}

	/**
	 * Set:  can_editown
	 *
	 * @param   integer  $canEditown  can_editown
	 *
	 * @return   $this
	 *
	 * @since  1.0
	 */
	public function setCanEditown($canEditown)
	{
		$this->canEditown = $canEditown;

		return $this;
	}

	/**
	 * Get:  system
	 *
	 * @return   integer
	 *
	 * @since  1.0
	 */
	public function getSystem()
	{
		return $this->system;
	}

	/**
	 * Set:  system
	 *
	 * @param   integer  $system  system
	 *
	 * @return   $this
	 *
	 * @since  1.0
	 */
	public function setSystem($system)
	{
		$this->system = $system;

		return $this;
	}

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
	public function bind($source, $ignore = array())
	{
		if (false == is_array($source))
		{
			throw new \UnexpectedValueException(__METHOD__ . ' only accepts arrays :(');
		}

		$src = new Registry($source);

		$filter = new InputFilter;

		$this->title = $filter->clean($src->get('title'));

		$this->group_id   = (int) $src->get('group_id');
		$this->project_id = (int) $src->get('project_id');
		$this->system     = (int) $src->get('system');

		// The following values come in as checkboxes ¡: "ON" or not set.
		$this->can_view    = $src->get('can_view')    ? 1 : 0;
		$this->can_create  = $src->get('can_create')  ? 1 : 0;
		$this->can_edit    = $src->get('can_edit')    ? 1 : 0;
		$this->can_editown = $src->get('can_editown') ? 1 : 0;
		$this->can_manage  = $src->get('can_manage')  ? 1 : 0;

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
