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
 * @Table(name="#__tracker_milestones",
 *    indexes={
 * @Index(name="name", columns={"title"}),
 * @Index(name="project_id", columns={"project_id"})
 *    }
 * )
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
	 * @Column(name="milestone_id", type="integer", length=11, nullable=false)
	 *
	 * @var  integer
	 *
	 * @since  1.0
	 */
	private $milestoneId;

	/**
	 * Milestone number from Github
	 *
	 * @Column(name="milestone_number", type="integer", length=11, nullable=false)
	 *
	 * @var  integer
	 *
	 * @since  1.0
	 */
	private $milestoneNumber;

	/**
	 * Project ID
	 *
	 * @Column(name="project_id", type="integer", length=11, nullable=false)
	 *
	 * @var  integer
	 *
	 * @since  1.0
	 */
	private $projectId;

	/**
	 * Milestone title
	 *
	 * @Column(name="title", type="string", length=50, nullable=false)
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	private $title;

	/**
	 * Milestone description
	 *
	 * @Column(name="description", type="text", nullable=false)
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	private $description;

	/**
	 * Label state: open | closed
	 *
	 * @Column(name="state", type="string", length=6, nullable=false)
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	private $state;

	/**
	 * Date the milestone is due on.
	 *
	 * @Column(name="due_on", type="datetime", nullable=true)
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	private $dueOn;

	/**
	 * Get:  PK
	 *
	 * @return   integer
	 *
	 * @since  1.0
	 */
	public function getMilestoneId()
	{
		return $this->milestoneId;
	}

	/**
	 * Set:  PK
	 *
	 * @param   integer  $milestoneId  PK
	 *
	 * @return   $this
	 *
	 * @since  1.0
	 */
	public function setMilestoneId($milestoneId)
	{
		$this->milestoneId = $milestoneId;

		return $this;
	}

	/**
	 * Get:  Milestone number from Github
	 *
	 * @return   integer
	 *
	 * @since  1.0
	 */
	public function getMilestoneNumber()
	{
		return $this->milestoneNumber;
	}

	/**
	 * Set:  Milestone number from Github
	 *
	 * @param   integer  $milestoneNumber  Milestone number from Github
	 *
	 * @return   $this
	 *
	 * @since  1.0
	 */
	public function setMilestoneNumber($milestoneNumber)
	{
		$this->milestoneNumber = $milestoneNumber;

		return $this;
	}

	/**
	 * Get:  Project ID
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
	 * Set:  Project ID
	 *
	 * @param   integer  $projectId  Project ID
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
	 * Get:  Milestone title
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
	 * Set:  Milestone title
	 *
	 * @param   string  $title  Milestone title
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
	 * Get:  Milestone description
	 *
	 * @return   string
	 *
	 * @since  1.0
	 */
	public function getDescription()
	{
		return $this->description;
	}

	/**
	 * Set:  Milestone description
	 *
	 * @param   string  $description  Milestone description
	 *
	 * @return   $this
	 *
	 * @since  1.0
	 */
	public function setDescription($description)
	{
		$this->description = $description;

		return $this;
	}

	/**
	 * Get:  Label state: open | closed
	 *
	 * @return   string
	 *
	 * @since  1.0
	 */
	public function getState()
	{
		return $this->state;
	}

	/**
	 * Set:  Label state: open | closed
	 *
	 * @param   string  $state  Label state: open | closed
	 *
	 * @return   $this
	 *
	 * @since  1.0
	 */
	public function setState($state)
	{
		$this->state = $state;

		return $this;
	}

	/**
	 * Get:  Date the milestone is due on.
	 *
	 * @return   string
	 *
	 * @since  1.0
	 */
	public function getDueOn()
	{
		return $this->dueOn;
	}

	/**
	 * Set:  Date the milestone is due on.
	 *
	 * @param   string  $dueOn  Date the milestone is due on.
	 *
	 * @return   $this
	 *
	 * @since  1.0
	 */
	public function setDueOn($dueOn)
	{
		$this->dueOn = $dueOn;

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
		parent::__construct('#__tracker_milestones', 'milestone_id', $database);
	}
}
