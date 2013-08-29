<?php
/**
 * Part of the Joomla Tracker's Groups Application
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Groups\View\Groups;

use App\Groups\Model\GroupsModel;

use Joomla\Language\Text;

use JTracker\View\AbstractTrackerHtmlView;
use JTracker\Container;

/**
 * The groups list view
 *
 * @since  1.0
 */
class GroupsHtmlView extends AbstractTrackerHtmlView
{
	/**
	 * Redefine the model so the correct type hinting is available.
	 *
	 * @var    GroupsModel
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
		// Set the vars to the template.
		$app = Container::retrieve('app');
		$this->renderer->set('items', $this->model->getItems());
		$this->renderer->set('project', $app->getProject());

		return parent::render();
	}
}
