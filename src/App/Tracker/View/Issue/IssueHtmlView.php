<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Tracker\View\Issue;

use App\Projects\TrackerProject;
use App\Tracker\Model\IssueModel;

use JTracker\View\AbstractTrackerHtmlView;

/**
 * The issues item view
 *
 * @since  1.0
 */
class IssueHtmlView extends AbstractTrackerHtmlView
{
	/**
	 * Redefine the model so the correct type hinting is available.
	 *
	 * @var     IssueModel
	 * @since   1.0
	 */
	protected $model;

	/**
	 * @var int
	 */
	private $id = 0;

	/**
	 * @var  TrackerProject
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
		$item = $this->model->getItem($this->getId());

		if (!$item->id)
		{
			// New item
			$path = __DIR__ . '/../../tpl/new-issue-template.md';

			if (!file_exists($path))
			{
				throw new \RuntimeException('New issue template not found.');
			}

			$item->issue_number    = 0;
			$item->priority        = 3;
			$item->description_raw = file_get_contents($path);
		}

		$this->renderer->set('item', $item);
		$this->renderer->set('project', $this->getProject());
		$this->renderer->set('statuses', $this->model->getStatuses());

		return parent::render();
	}

	/**
	 * Get the id.
	 *
	 * @return  integer
	 *
	 * @since   1.0
	 */
	public function getId()
	{
		if (0 == $this->id)
		{
			// New record.
		}

		return $this->id;
	}

	/**
	 * Set the project.
	 *
	 * @param   integer  $id  The id
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	public function setId($id)
	{
		$this->id = $id;

		return $this;
	}

	/**
	 * Get the project.
	 *
	 * @throws  \RuntimeException
	 * @return  TrackerProject
	 *
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
