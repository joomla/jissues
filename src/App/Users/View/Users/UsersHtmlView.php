<?php
/**
 * Part of the Joomla Tracker's Users Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Users\View\Users;

use JTracker\View\AbstractTrackerHtmlView;

/**
 * Users view class for the Users component
 *
 * @since  1.0
 */
class UsersHtmlView extends AbstractTrackerHtmlView
{
	/**
	 * A list of items.
	 *
	 * @var  array
	 *
	 * @since  1.0
	 */
	private $items = [];

	/**
	 * Method to render the view.
	 *
	 * @return  string  The rendered view.
	 *
	 * @since   1.0
	 */
	public function render()
	{
		$this->renderer->set('items', $this->getItems());

		// @TODO pagination
		// $this->renderer->set('pagination', $this->model->getPagination());

		return parent::render();
	}

	/**
	 * Get the items.
	 *
	 * @return array
	 *
	 * @since   1.0
	 */
	public function getItems()
	{
		return $this->items;
	}

	/**
	 * Set the items.
	 *
	 * @param   array  $items  The items
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	public function setItems(array $items)
	{
		$this->items = $items;

		return $this;
	}
}
