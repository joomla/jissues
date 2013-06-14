<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Users\View\User;

use Joomla\Factory;
use JTracker\View\AbstractTrackerHtmlView;

/**
 * User view class for the Users component
 *
 * @since  1.0
 */
class UserHtmlView extends AbstractTrackerHtmlView
{
	/**
	 * @var \App\Users\Model\UserModel
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
		$this->renderer->set('item', $this->model->getItem(Factory::$application->input->getUint('id')));

		return parent::render();
	}
}
