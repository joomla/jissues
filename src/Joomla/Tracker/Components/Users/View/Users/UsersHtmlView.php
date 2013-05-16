<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tracker\Components\Users\View\Users;

use Joomla\Tracker\Components\Users\Model\UsersModel;
use Joomla\Tracker\View\AbstractTrackerHtmlView;

/**
 * View class for the tracker component
 *
 * @package  JTracker\Components\Tracker
 * @since    1.0
 */
class UsersHtmlView extends AbstractTrackerHtmlView
{
	protected $items = array();

	/**
	 * @var UsersModel
	 */
	protected $model;

	/**
	 * Method to render the view.
	 *
	 * @return  string  The rendered view.
	 *
	 * @since   1.0
	 */
	public function render()
	{
		$this->renderer->set('items', $this->model->getItems());

		return parent::render();
	}
}
