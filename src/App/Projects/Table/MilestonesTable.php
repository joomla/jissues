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
 * Table interface class for the "tracker_milestones" database table.
 *
 * @Entity
 * @Table(name="#__tracker_milestones")
 *
 * @since  1.0
 */
class MilestonesTable extends AbstractDatabaseTable
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
	public $milestone_id;

	/**
	 * Milestone number from Github
	 *
	 * @Column(type="integer", length=11)
	 *
	 * @var  integer
	 *
	 * @since  1.0
	 */
	public $milestone_number;

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
	 * Milestone title
	 *
	 * @Column(type="string", length=50)
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	public $title;

	/**
	 * Milestone description
	 *
	 * @Column(type="text")
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	public $description;

	/**
	 * Label state: open | closed
	 *
	 * @Column(type="string", length=6)
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	public $state;

	/**
	 * Date the milestone is due on.
	 *
	 * @Column(type="datetime")
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	public $due_on;

	/**
	 * Constructor
	 *
	 * @param   DatabaseDriver  $database  A database connector object
	 *
	 * @since   1.0
	 */
	public function __construct(DatabaseDriver $database)
	{
		parent::__construct('#__tracker_milestones', 'milestone_id', $database);
	}
}
