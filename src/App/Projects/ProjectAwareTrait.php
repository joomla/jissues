<?php
/**
 * Part of the Joomla Tracker's Projects Application
 *
 * @copyright  Copyright (C) 2012-2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Projects;

/**
 * Decorator for an object which is aware of a TrackerProject instance
 *
 * @since  1.0
 */
trait ProjectAwareTrait
{
	/**
	 * Project object
	 *
	 * @var    TrackerProject
	 * @since  1.0
	 */
	private $project;

	/**
	 * Get the project.
	 *
	 * @return  TrackerProject
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	public function getProject()
	{
		if ($this->project)
		{
			return $this->project;
		}

		throw new \UnexpectedValueException('Project not set in ' . __CLASS__);
	}

	/**
	 * Set the project.
	 *
	 * @param   TrackerProject  $project  The project.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	public function setProject(TrackerProject $project)
	{
		$this->project = $project;

		return $this;
	}
}
