<?php
/**
 * Part of the Joomla Tracker's GitHub Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\GitHub\View\Stats;

use JTracker\View\AbstractTrackerHtmlView;

/**
 * System statistics view.
 *
 * @since  1.0
 */
class StatsHtmlView extends AbstractTrackerHtmlView
{
	/**
	 * Config object.
	 *
	 * @var    \stdClass
	 * @since  1.0
	 */
	protected $config;

	/**
	 * Contributors data object for the view
	 *
	 * @var    object
	 * @since  1.0
	 */
	protected $contributors;

	/**
	 * Method to render the view.
	 *
	 * @return  string  The rendered view.
	 *
	 * @since   1.0
	 */
	public function render()
	{
		$this->addData('data', $this->getContributors())
			->addData('project', $this->getProject());

		return parent::render();
	}

	/**
	 * Get the data object.
	 *
	 * @return  object
	 *
	 * @since   1.0
	 * @throws  \UnexpectedValueException
	 */
	public function getContributors()
	{
		if ($this->contributors === null)
		{
			throw new \UnexpectedValueException('Contributor data not set.');
		}

		return $this->contributors;
	}

	/**
	 * Set the data.
	 *
	 * @param   object  $data  The data object.
	 *
	 * @return  $this  Method allows chaining
	 *
	 * @since   1.0
	 */
	public function setContributors($data)
	{
		$this->contributors = $data;

		return $this;
	}
}
