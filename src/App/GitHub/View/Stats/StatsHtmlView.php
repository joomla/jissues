<?php
/**
 * Part of the Joomla Tracker's GitHub Application
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
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
	 * @var  object
	 */
	protected $data = null;

	/**
	 * Method to render the view.
	 *
	 * @throws \DomainException
	 * @throws \Exception
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
	 * @throws \UnexpectedValueException
	 * @return null
	 *
	 * @since   1.0
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
	 * @return $this
	 *
	 * @since   1.0
	 */
	public function setData($data)
	{
		$this->data = $data;

		return $this;
	}
}
