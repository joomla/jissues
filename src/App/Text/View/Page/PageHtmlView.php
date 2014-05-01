<?php
/**
 * Part of the Joomla Tracker's Text Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Text\View\Page;

use App\Text\Entity\Article;

use JTracker\Router\Exception\RoutingException;
use JTracker\View\AbstractTrackerHtmlView;

/**
 * Page view class
 *
 * @since  1.0
 */
class PageHtmlView extends AbstractTrackerHtmlView
{
	/**
	 * The page.
	 *
	 * @var    \App\Text\Entity\Article
	 * @since  1.0
	 */
	protected $item;

	/**
	 * Method to render the view.
	 *
	 * @return  string  The rendered view.
	 *
	 * @since   1.0
	 * @throws  RoutingException
	 */
	public function render()
	{
		$this->renderer->set('page', $this->getItem());

		return parent::render();
	}

	/**
	 * Get the item.
	 *
	 * @return  Article
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	public function getItem()
	{
		if (!$this->item)
		{
			throw new \RuntimeException('Item not set.');
		}

		return $this->item;
	}

	/**
	 * Set the page alias.
	 *
	 * @param   Article  $item  The item.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	public function setItem(Article $item)
	{
		$this->item = $item;

		return $this;
	}
}
