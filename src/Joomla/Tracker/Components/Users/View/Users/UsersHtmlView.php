<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tracker\Components\Users\View\Users;

use Joomla\View\AbstractHtmlView;

/**
 * View class for the tracker component
 *
 * @package  JTracker\Components\Tracker
 * @since    1.0
 */
class UsersHtmlView extends AbstractHtmlView
{
	protected $items = array();

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
		$this->items = $this->model->getItems();

		return parent::render();
	}
}
