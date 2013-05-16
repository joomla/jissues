<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tracker\Components\Users\View\User;

use Joomla\Factory;
use Joomla\Tracker\View\AbstractTrackerHtmlView;

/**
 * Default view class for the tracker component
 *
 * @package  JTracker\Components\Tracker
 * @since    1.0
 */
class UserHtmlView extends AbstractTrackerHtmlView
{
	protected $item;

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
