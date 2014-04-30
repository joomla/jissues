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
 * @Table(name="#__tracker_labels",
 *    indexes={
 * @Index(name="name", columns={"name"}),
 * @Index(name="project_id", columns={"project_id"})
 *    }
 * )
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
	 * @Column(name="label_id", type="integer", length=11, nullable=false)
	 *
	 * @var  integer
	 *
	 * @since  1.0
	 */
	private $labelId;

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
	 * Label name
	 *
	 * @Column(name="name", type="string", length=50, nullable=false)
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	private $name;

	/**
	 * Label color
	 *
	 * @Column(name="color", type="string", length=6, nullable=false)
	 *
	 * @var  string
	 *
	 * @since  1.0
	 */
	private $color;

	/**
	 * Get:  PK
	 *
	 * @return   integer
	 *
	 * @since  1.0
	 */
	public function getLabelId()
	{
		return $this->labelId;
	}

	/**
	 * Set:  PK
	 *
	 * @param   integer  $labelId  PK
	 *
	 * @return   $this
	 *
	 * @since  1.0
	 */
	public function setLabelId($labelId)
	{
		$this->labelId = $labelId;

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
	 * Get:  Label name
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
	 * Set:  Label name
	 *
	 * @param   string  $name  Label name
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
	 * Get:  Label color
	 *
	 * @return   string
	 *
	 * @since  1.0
	 */
	public function getColor()
	{
		return $this->color;
	}

	/**
	 * Set:  Label color
	 *
	 * @param   string  $color  Label color
	 *
	 * @return   $this
	 *
	 * @since  1.0
	 */
	public function setColor($color)
	{
		$this->color = $color;

		return $this;
	}

	/**
	 * @var ProjectsTable
	 *
	 * @ORM\ManyToOne(targetEntity="ProjectsTable")
	 * @ORM\JoinColumns({
	 * @ORM\JoinColumn(name="project_id", referencedColumnName="project_id")
	 * })
	 */
	private $project;

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

	/**
	 * Get the project.
	 *
	 * @return ProjectsTable
	 *
	 * @since   1.0
	 */
	public function getProject()
	{
		return $this->project;
	}

	/**
	 * Set the project.
	 *
	 * @param   ProjectsTable  $project  The project.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	public function setProject(ProjectsTable $project)
	{
		$this->project = $project;

		return $this;
	}
}
