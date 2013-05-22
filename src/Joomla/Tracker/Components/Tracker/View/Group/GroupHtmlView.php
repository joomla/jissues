<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tracker\Components\Tracker\View\Group;

use Joomla\Language\Text;
use Joomla\Tracker\Components\Tracker\Model\GroupModel;
use Joomla\Tracker\Components\Tracker\Model\ProjectModel;
use Joomla\Tracker\View\AbstractTrackerHtmlView;
use Joomla\Utilities\ArrayHelper;

/**
 * The issues list view
 *
 * @package  JTracker\Components\Tracker
 * @since    1.0
 */
class GroupHtmlView extends AbstractTrackerHtmlView
{
	/**
	 * Container for the view's items
	 *
	 * @var    array
	 * @since  1.0
	 */
	protected $items;

	/**
	 * Redefine the model so the correct type hinting is available.
	 *
	 * @var     GroupModel
	 * @since   1.0
	 */
	protected $model;

	/**
	 * @var    \stdClass
	 * @since  1.0
	 */
	protected $project;

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
		$projectModel = new ProjectModel;

		// Set the vars to the template.
		$this->renderer->set('group', ArrayHelper::fromObject($this->model->getItem()));
		$this->renderer->set('project', $projectModel->getItem()->getIterator());

		return parent::render();
	}
}
