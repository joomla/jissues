<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Tracker\View\Issue;

use Joomla\Language\Text;
use App\Tracker\Model\IssueModel;
use App\Tracker\Table\IssuesTable;
use JTracker\View\AbstractTrackerHtmlView;
use JTracker\Container;

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
	 * Method to render the view.
	 *
	 * @return  string  The rendered view.
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	public function render()
	{
		/* @type \JTracker\Application $application */
		$application = Container::retrieve('app');

		$id = $application->input->getUint('id');

		if ($id)
		{
			// Edit item
			$item = $this->model->getItem($id);
		}
		else
		{
			// New item
			$item = new IssuesTable(Container::retrieve('db'));

			$path = __DIR__ . '/../../tpl/new-issue-template.md';

			if (!file_exists($path))
			{
				throw new \RuntimeException('New issue template not found.');
			}

			$item->issue_number    = 0;
			$item->priority        = 3;
			$item->description_raw = file_get_contents($path);

			$item = $item->getIterator();
		}

		$this->renderer->set('item', $item);
		$this->renderer->set('project', $application->getProject());
		$this->renderer->set('statuses', $this->model->getStatuses());

		return parent::render();
	}
}
