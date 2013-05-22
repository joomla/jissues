<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tracker\Components\Tracker\View\Issues;

use Joomla\Language\Text;
use Joomla\Tracker\Components\Tracker\Model\IssuesModel;
use Joomla\Tracker\View\AbstractTrackerHtmlView;

/**
 * The issues list view
 *
 * @since  1.0
 */
class IssuesHtmlView extends AbstractTrackerHtmlView
{
	/**
	 * Redefine the model so the correct type hinting is available.
	 *
	 * @var     IssuesModel
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
		// Set the vars to the template.
		$this->renderer->set('items', $this->model->getItems());
		$this->renderer->set('pagination', $this->model->getPagination());
		$this->renderer->set('state', $this->model->getState());
		$this->renderer->set('project', $this->model->getProject());

		return parent::render();
	}
}
