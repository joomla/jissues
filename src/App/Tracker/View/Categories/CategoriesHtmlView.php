<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Tracker\View\Categories;


use JTracker\View\AbstractTrackerHtmlView;
use App\Tracker\Model\CategoriesModel;
use App\Projects\TrackerProject;

/**
 * The category list view
 *
 * @since  1.0
 */
class CategoriesHtmlView extends AbstractTrackerHtmlView
{
	/**
	 * Redefine the model so the correct type hinting is available.
	 *
	 * @var     CategoriesModel
	 * @since   1.0
	 */
	protected $model;

	/**
	 * Project object
	 *
	 * @var    TrackerProject
	 * @since  1.0
	 */
	protected $project = null;

	/**
	 * Method to render the view.
	 *
	 * @return  string  The rendered view.
	 *
	 * @throws  \RuntimeException
	 * @since   1.0
	 */
	public function render()
	{
		// Set the vars to the template.
		$this->renderer->set('items', $this->model->getItems());
		$this->renderer->set('pagination', $this->model->getPagination());
		$this->renderer->set('state', $this->model->getState());
		$this->renderer->set('project', $this->getProject());

		return parent::render();
	}

	/**
	 * Get the project.
	 *
	 * @return  TrackerProject
	 *
	 * @throws  \RuntimeException
	 * @since   1.0
	 */
	public function getProject()
	{
		if (is_null($this->project))
		{
			throw new \RuntimeException('No project set.');
		}

		return $this->project;
	}

	/**
	 * Set the project.
	 *
	 * @param   TrackerProject  $project  The project.
	 *
	 * @return  $this  Method allows chaining
	 *
	 * @since   1.0
	 */
	public function setProject(TrackerProject $project)
	{
		$this->project = $project;

		return $this;
	}
}
