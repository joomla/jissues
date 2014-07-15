<?php
/**
 * Created by PhpStorm.
 * User: allenzhao
 * Date: 7/10/14
 * Time: 12:49 PM
 */

namespace App\Tracker\View\Category;


use JTracker\View\AbstractTrackerHtmlView;
use App\Tracker\Model\CategoryModel;
use App\Projects\TrackerProject;
use App\Tracker\Table\CategoryTable;

class CategoryHtmlView extends AbstractTrackerHtmlView
{

	/**
	 * Redefine the model so the correct type hinting is available.
	 *
	 * @var     CategoryModel
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
	 *
	 *
	 * @var CategoryTable
	 * @since 1.0
	 */
	protected $item = null;

	/**
	 * @param mixed $item
	 *
	 * @return $this    Method allows chaining
	 */
	public function setItem($item)
	{
		$this->item = $item;

		return $this;
	}

	/**
	 * @throws \RuntimeException
	 *
	 * @return mixed
	 */
	public function getItem()
	{
		if (is_null($this->item))
		{
			throw new \RuntimeException('Item not set.');
		}

		return $this->item;
	}

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
		$this->renderer->set('state', $this->model->getState());
		$this->renderer->set('project', $this->getProject());
		$this->renderer->set('item', $this->getItem());

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
	 * @param   TrackerProject $project The project.
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
