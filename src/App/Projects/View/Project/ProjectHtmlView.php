<?php
/**
 * Part of the Joomla Tracker's Projects Application
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Projects\View\Project;

use JTracker\View\AbstractTrackerHtmlView;

/**
 * The projects item view
 *
 * @since  1.0
 */
class ProjectHtmlView extends AbstractTrackerHtmlView
{
	/**
	 * The model object.
	 *
	 * @var    \App\Projects\Model\ProjectModel
	 * @since  1.0
	 */
	protected $model;

	/**
	 * @var string
	 */
	protected $alias = '';

	/**
	 * Method to render the view.
	 *
	 * @return  string  The rendered view.
	 *
	 * @since   1.0
	 */
	public function render()
	{
		$this->renderer->set('project', $this->model->getByAlias($this->getAlias()));

		return parent::render();
	}

	/**
	 * Get the alias.
	 *
	 * @return string
	 *
	 * @since   1.0
	 */
	public function getAlias()
	{
		if ('' == $this->alias)
		{
			// New record.
		}

		return $this->alias;
	}

	/**
	 * Set the alias.
	 *
	 * @param   string  $alias  The alias.
	 *
	 * @return $this
	 *
	 * @since   1.0
	 */
	public function setAlias($alias)
	{
		$this->alias = $alias;

		return $this;
	}
}
