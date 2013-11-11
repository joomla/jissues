<?php
/**
 * Part of the Joomla Tracker's Text Application
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Text\View\Article;

use App\Text\Model\ArticleModel;
use App\Text\Table\ArticlesTable;

use JTracker\View\AbstractTrackerHtmlView;

/**
 * Article view class
 *
 * @since  1.0
 */
class ArticleHtmlView extends AbstractTrackerHtmlView
{
	/**
	 * Redefine the model so the correct type hinting is available.
	 *
	 * @var     ArticleModel
	 * @since   1.0
	 */
	protected $model;

	/**
	 * @var ArticlesTable
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
	 * @return \App\Text\Table\ArticlesTable
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
	 * @param   ArticlesTable  $item  The item.
	 *
	 * @return $this
	 *
	 * @since   1.0
	 */
	public function setItem($item)
	{
		$this->item = $item;

		return $this;
	}
}
