<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Table;

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
	 * @Column(type="integer", length=11)
	 *
	 * @var  integer
	 *
	 * @since  1.0
	 */
	public $user_id;

	/**
	 * Foreign Key to #__accessgroups.id
	 *
	 * @Id
	 * @Column(type="integer", length=11)
	 *
	 * @var  integer
	 *
	 * @since  1.0
	 */
	public $group_id;

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
