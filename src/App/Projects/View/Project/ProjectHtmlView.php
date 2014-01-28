<?php
/**
 * Part of the Joomla Tracker's Projects Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
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
	 * Project alias
	 *
	 * @var    string
	 * @since  1.0
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
	 * @return  string
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
	 * @return  $this  Method allows chaining
	 *
	 * @since   1.0
	 */
	public function setAlias($alias)
	{
		$this->alias = $alias;

		return $this;
	}
}
