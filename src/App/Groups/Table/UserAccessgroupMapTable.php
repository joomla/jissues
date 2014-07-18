<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Groups\Table;

use Joomla\Database\DatabaseDriver;

use JTracker\Database\AbstractDatabaseTable;

/**
 * Table interface class for the "user_accessgroup_map" database table.
 *
 * @Entity
 * @Table(name="#__user_accessgroup_map")
 *
 * @since  1.0
 */
class UserAccessgroupMapTable extends AbstractDatabaseTable
{
	/**
	 * Foreign Key to #__users.id
	 *
	 * @Id
	 * @Column(name="user_id", type="integer", length=11, nullable=false)
	 *
	 * @var  integer
	 *
	 * @since  1.0
	 */
	private $userId;

	/**
	 * Foreign Key to #__accessgroups.id
	 *
	 * @Id
	 * @Column(name="group_id", type="integer", length=11, nullable=false)
	 *
	 * @var  integer
	 *
	 * @since  1.0
	 */
	private $groupId;

	/**
	 * Get:  Foreign Key to #__users.id
	 *
	 * @return   integer
	 *
	 * @since  1.0
	 */
	public function getUserId()
	{
		return $this->userId;
	}

	/**
	 * Set:  Foreign Key to #__users.id
	 *
	 * @param   integer  $userId  Foreign Key to #__users.id
	 *
	 * @return   $this
	 *
	 * @since  1.0
	 */
	public function setUserId($userId)
	{
		$this->userId = $userId;

		return $this;
	}

	/**
	 * Get:  Foreign Key to #__accessgroups.id
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
	 * Set:  Foreign Key to #__accessgroups.id
	 *
	 * @param   integer  $groupId  Foreign Key to #__accessgroups.id
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
	 * Constructor
	 *
	 * @param   DatabaseDriver  $database  A database connector object.
	 *
	 * @since   1.0
	 */
	public function __construct(DatabaseDriver $database)
	{
		parent::__construct('#__user_accessgroup_map', 'id', $database);
	}
}
