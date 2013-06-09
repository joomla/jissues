<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Tracker\View\Issue;

use Joomla\Factory;
use Joomla\Language\Text;
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
	 * Container for the view's items
	 *
	 * @var    object
	 * @since  1.0
	 */
	protected $item;

	/**
	 * Redefine the model so the correct type hinting is available.
	 *
	 * @var     IssueModel
	 * @since   1.0
	 */
	protected $model;

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
		/* @type \JTracker\Application\TrackerApplication $application */
		$application = Factory::$application;

		$id = $application->input->getUint('id');

		if ($id)
		{
			$item = $this->model->getItem($id);
		}
		else
		{
			$item = new IssuesTable($application->getDatabase());
		}

		$this->renderer->set('item', $item);
		$this->renderer->set('project', $application->getProject());
		$this->renderer->set('statuses', $this->model->getStatuses());

		return parent::render();
	}
}
