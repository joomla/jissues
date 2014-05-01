<?php
/**
 * Part of the Joomla Tracker's Text Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Text\View\Article;

use App\Text\Entity\Article;

use JTracker\View\AbstractTrackerHtmlView;

/**
 * Article view class
 *
 * @since  1.0
 */
class ArticleHtmlView extends AbstractTrackerHtmlView
{
	/**
	 * Table object with article data
	 *
	 * @var    Article
	 * @since  1.0
	 */
	protected $item = null;

	/**
	 * Method to render the view.
	 *
	 * @return  string  The rendered view.
	 *
	 * @since   1.0
	 */
	public function render()
	{
		$this->renderer->set('item', $this->getItem());

		return parent::render();
	}

	/**
	 * Get the item.
	 *
	 * @return  Article
	 *
	 * @since   1.0
	 */
	public function getItem()
	{
		return $this->item;
	}

	/**
	 * Set the item.
	 *
	 * @param   Article  $item  The item.
	 *
	 * @return  $this  Method supports chaining
	 *
	 * @since   1.0
	 */
	public function setItem(Article $item)
	{
		$this->item = $item;

		return $this;
	}
}
