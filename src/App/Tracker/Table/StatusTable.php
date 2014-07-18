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
 * @Table(name="#__status")
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
	 * @Column(name="id", type="integer", length=11, nullable=false)
	 *
	 * @var  integer
	 *
	 * @since  1.0
	 */
	private $id;

	/**
	 * status
	 *
	 * @Column(name="status", type="string", length=255, nullable=true)
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	private $status;

	/**
	 * closed
	 *
	 * @Column(name="closed", type="smallint", length=4, nullable=false)
	 *
	 * @var  integer
	 *
	 * @since  1.0
	 */
	private $closed;

	/**
	 * Get:  id
	 *
	 * @return   integer
	 *
	 * @since  1.0
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * Set:  id
	 *
	 * @param   integer  $id  id
	 *
	 * @return   $this
	 *
	 * @since  1.0
	 */
	public function setId($id)
	{
		$this->id = $id;

		return $this;
	}

	/**
	 * Get:  status
	 *
	 * @return   string
	 *
	 * @since  1.0
	 */
	public function getStatus()
	{
		return $this->status;
	}

	/**
	 * Set:  status
	 *
	 * @param   string  $status  status
	 *
	 * @return   $this
	 *
	 * @since  1.0
	 */
	public function setStatus($status)
	{
		$this->status = $status;

		return $this;
	}

	/**
	 * Get:  closed
	 *
	 * @return   integer
	 *
	 * @since  1.0
	 */
	public function getClosed()
	{
		return $this->closed;
	}

	/**
	 * Set:  closed
	 *
	 * @param   integer  $closed  closed
	 *
	 * @return   $this
	 *
	 * @since  1.0
	 */
	public function setClosed($closed)
	{
		$this->closed = $closed;

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
		parent::__construct('#__status', 'id', $database);
	}
}
