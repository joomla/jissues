<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tracker\Components\Tracker\View\Project;

use Joomla\Tracker\Components\Tracker\Model\ProjectModel;
use Joomla\Tracker\View\AbstractTrackerHtmlView;
use Joomla\Utilities\ArrayHelper;

/**
 * The projects item view
 *
 * @since  1.0
 */
class ProjectHtmlView extends AbstractTrackerHtmlView
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
	 * @var    ProjectModel
	 * @since  1.0
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
		$this->renderer->set('project', ArrayHelper::fromObject($this->model->getItem()->getIterator()));

		// Screw Twig.....
		// $this->renderer->set('project', $this->model->getItem());

		return parent::render();
	}
}
