<?php
/**
 * Created by PhpStorm.
 * User: allenzhao
 * Date: 7/10/14
 * Time: 9:31 PM
 */

namespace App\Tracker\View\Categories;


use JTracker\View\AbstractTrackerHtmlView;
use App\Tracker\Model\CategoriesModel;
use App\Projects\TrackerProject;

class CategoriesHtmlView extends AbstractTrackerHtmlView {

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
	 * @since   1.0
	 * @throws  \RuntimeException
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
	 * @since   1.0
	 * @throws  \RuntimeException
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
	}}