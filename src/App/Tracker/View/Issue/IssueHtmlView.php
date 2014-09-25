<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\View\Issue;

use App\Projects\TrackerProject;
use App\Tracker\Model\IssueModel;
use App\Tracker\Table\IssuesTable;

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
	 * Project object
	 *
	 * @var    TrackerProject
	 * @since  1.0
	 */
	protected $project = null;

	/**
	 * Item object
	 *
	 * @var    IssuesTable
	 * @since  1.0
	 */
	protected $item = null;

	/**
	 * If the user has "edit own" rights.
	 *
	 * @var    boolean
	 * @since  1.0
	 */
	protected $editOwn = false;

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
		$this->renderer->set('item', $this->getItem());
		$this->renderer->set('project', $this->getProject());
		$this->renderer->set('statuses', $this->model->getStatuses());
		$this->renderer->set('canEditOwn', $this->canEditOwn());

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
	}

	/**
	 * Get the item.
	 *
	 * @throws \RuntimeException
	 * @return IssuesTable
	 *
	 * @since   1.0
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
	 * Set the item.
	 *
	 * @param   IssuesTable  $item  The item to set.
	 *
	 * @return $this
	 *
	 * @since   1.0
	 */
	public function setItem($item)
	{
		$this->item = $item;

		return $this;
	}

	/**
	 * Check if the user is allowed to edit her own issues.
	 *
	 * @return  boolean
	 *
	 * @since   1.0
	 */
	public function canEditOwn()
	{
		return $this->editOwn;
	}

	/**
	 * Set if the user is allowed to edit her own issues.
	 *
	 * @param   boolean  $editOwn  If the user is allowed.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	public function setEditOwn($editOwn)
	{
		$this->editOwn = (bool) $editOwn;

		return $this;
	}
}
