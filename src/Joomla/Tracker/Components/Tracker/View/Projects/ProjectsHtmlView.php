<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tracker\Components\Tracker\View\Projects;

use Joomla\Tracker\Components\Tracker\Model\ProjectsModel;
use Joomla\Tracker\View\AbstractTrackerHtmlView;
use Symfony\Component\Yaml\Exception\RuntimeException;

/**
 * The issues item view
 *
 * @package  JTracker\Components\Tracker
 * @since    1.0
 */
class ProjectsHtmlView extends AbstractTrackerHtmlView
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
	 * @var     ProjectsModel
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
		$this->renderer->set('projects', $this->model->getItems());

		return parent::render();
	}
}
