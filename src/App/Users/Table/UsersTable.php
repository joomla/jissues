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
 * Table interface class for the "users" database table.
 *
 * @Entity
 * @Table(name="#__users")
 *
 * @since  1.0
 */
class UsersTable extends AbstractDatabaseTable
{
	/**
	 * PK
	 *
	 * @Id
	 * @GeneratedValue
	 * @Column(type="integer", length=11)
	 *
	 * @var  integer
	 *
	 * @since  1.0
	 */
	public $id;

	/**
	 * The users name
	 *
	 * @Column(type="string", length=255)
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	public $name;

	/**
	 * The users username
	 *
	 * @Column(type="string", length=150)
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	public $username;

	/**
	 * The users e-mail
	 *
	 * @Column(type="string", length=100)
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	public $email;

	/**
	 * If the user is blocked
	 *
	 * @Column(type="smallint", length=4)
	 *
	 * @var  integer
	 *
	 * @since  1.0
	 */
	public $block;

	/**
	 * If the users recieves e-mail
	 *
	 * @Column(type="smallint", length=4)
	 *
	 * @var  integer
	 *
	 * @since  1.0
	 */
	public $sendEmail;

	/**
	 * The register date
	 *
	 * @Column(type="datetime")
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	public $registerDate;

	/**
	 * The last visit date
	 *
	 * @Column(type="datetime")
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	public $lastvisitDate;

	/**
	 * Parameters
	 *
	 * @Column(type="text")
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	public $params;

	/**
	 * Constructor
	 *
	 * @param   DatabaseDriver  $database  A database connector object.
	 *
	 * @since   1.0
	 */
	public function __construct(DatabaseDriver $database)
	{
		parent::__construct('#__users', 'id', $database);
	}
}
