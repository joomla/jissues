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
 * Table interface class for the "status" database table.
 *
 * @Entity
 * @Table(name="_status")
 *
 * @since  1.0
 */
class StatusTable extends AbstractDatabaseTable
{
	/**
	 * id
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
	 * status
	 *
	 * @Column(type="string", length=255)
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	public $status;

	/**
	 * closed
	 *
	 * @Column(type="smallint", length=4)
	 *
	 * @var  integer
	 *
	 * @since  1.0
	 */
	public $closed;

	/**
	 * Constructor
	 *
	 * @param   DatabaseDriver  $database  A database connector object.
	 *
	 * @since   1.0
	 */
	public function __construct(DatabaseDriver $database)
	{
		parent::__construct('#__status', 'id', $database);
	}
}
