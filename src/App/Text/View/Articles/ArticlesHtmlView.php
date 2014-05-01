<?php
/**
 * Part of the Joomla Tracker's Text Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Text\View\Articles;

use JTracker\View\AbstractTrackerHtmlView;

/**
 * Articles view class
 *
 * @since  1.0
 */
class ArticlesHtmlView extends AbstractTrackerHtmlView
{
	/**
	 * The items for this view..
	 *
	 * @var    array
	 * @since  1.0
	 */
	protected $items = [];

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
