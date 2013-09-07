<?php
/**
 * Part of the Joomla Tracker's Users Application
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Users\View\User;

use App\Users\Model\UserModel;

use JTracker\View\AbstractTrackerHtmlView;
use JTracker\Container;

/**
 * User view class for the Users component
 *
 * @since  1.0
 */
class UserHtmlView extends AbstractTrackerHtmlView
{
	/**
	 * The model object.
	 *
	 * @var    UserModel
	 * @since  1.0
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
		$app = Container::retrieve('app');
		$this->renderer->set('item', $this->model->getItem($app->input->getUint('id')));

		return parent::render();
	}
}
