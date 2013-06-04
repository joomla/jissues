<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tracker\Components\Tracker\View\Project;

use Joomla\Tracker\View\AbstractTrackerHtmlView;

/**
 * The projects item view
 *
 * @since  1.0
 */
class ProjectHtmlView extends AbstractTrackerHtmlView
{
	/**
	 * @var  \Joomla\Tracker\Components\Tracker\Model\ProjectModel
	 */
	protected $model;

	/**
	 * Method to render the view.
	 *
	 * @since   1.0
	 * @return  string  The rendered view.
	 */
	public function render()
	{
		$this->renderer->set('project', $this->model->getByAlias());

		return parent::render();
	}
}
