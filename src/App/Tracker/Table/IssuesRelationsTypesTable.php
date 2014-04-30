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
 * Table interface class for the "issues_relations_types" database table.
 *
 * @Entity
 * @Table(name="#__issues_relations_types")
 *
 * @since  1.0
 */
class IssuesRelationsTypesTable extends AbstractDatabaseTable
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
	 * name
	 *
	 * @Column(name="name", type="string", length=150, nullable=false)
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	private $name;

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
	 * Get:  name
	 *
	 * @return   string
	 *
	 * @since  1.0
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Set:  name
	 *
	 * @param   string  $name  name
	 *
	 * @return   $this
	 *
	 * @since  1.0
	 */
	public function setName($name)
	{
		$this->name = $name;

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
		parent::__construct('#__issues_relations_types', 'id', $database);
	}
}
