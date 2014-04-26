<?php
/**
 * Part of the Joomla Tracker's Projects Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Projects\Table;

use Joomla\Database\DatabaseDriver;

use JTracker\Database\AbstractDatabaseTable;

/**
 * Table interface class for the "tracker_labels" database table.
 *
 * @Entity
 * @Table(name="#__tracker_labels")
 *
 * @since  1.0
 */
class LabelsTable extends AbstractDatabaseTable
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
	public $label_id;

	/**
	 * Project ID
	 *
	 * @Column(type="integer", length=11)
	 *
	 * @var  integer
	 *
	 * @since  1.0
	 */
	public $project_id;

	/**
	 * Label name
	 *
	 * @Column(type="string", length=50)
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	public $name;

	/**
	 * Label color
	 *
	 * @Column(type="string", length=6)
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	public $color;

	/**
	 * Constructor
	 *
	 * @param   DatabaseDriver  $database  A database connector object
	 *
	 * @since   1.0
	 */
	public function __construct(DatabaseDriver $database)
	{
		parent::__construct('#__tracker_labels', 'label_id', $database);
	}
}
