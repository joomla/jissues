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
	 * Data object for the view
	 *
	 * @var    object
	 * @since  1.0
	 */
	protected $data = null;

	/**
	 * Method to render the view.
	 *
	 * @return  string  The rendered view.
	 *
	 * @since   1.0
	 */
	public function render()
	{
		$this->renderer
			->set('data', $this->getData())
			->set('project', $this->getProject());

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
	public function getData()
	{
		if (is_null($this->data))
		{
			throw new \UnexpectedValueException('Data not set.');
		}

		return $this->data;
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
	public function setData($data)
	{
		$this->data = $data;

		return $this;
	}
}
