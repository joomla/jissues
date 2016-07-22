<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Tracker\View\Category;

use JTracker\View\AbstractTrackerHtmlView;
use App\Tracker\Model\CategoryModel;
use App\Tracker\Table\CategoryTable;

/**
 * The category view
 *
 * @since  1.0
 */
class CategoryHtmlView extends AbstractTrackerHtmlView
{
	/**
	 * Redefine the model so the correct type hinting is available.
	 *
	 * @var     CategoryModel
	 * @since   1.0
	 */
	protected $model;

	/**
	 * Item object
	 *
	 * @var CategoryTable
	 *
	 * @since 1.0
	 */
	protected $item = null;

	/**
	 * Set the item
	 *
	 * @param   CategoryTable  $item  The item to set
	 *
	 * @return  $this    Method allows chaining
	 *
	 * @since  1.0
	 */
	public function setItem($item)
	{
		$this->item = $item;

		return $this;
	}

	/**
	 * Get the item
	 *
	 * @throws \RuntimeException
	 *
	 * @return CategoryTable
	 *
	 * @since  1.0
	 */
	public function getItem()
	{
		if (is_null($this->item))
		{
			throw new \RuntimeException('Item not set.');
		}

		return $this->item;
	}

	/**
	 * Method to render the view.
	 *
	 * @return  string  The rendered view.
	 *
	 * @throws  \RuntimeException
	 *
	 * @since  1.0
	 */
	public function render()
	{
		// Set the vars to the template.
		$this->addData('state', $this->model->getState());
		$this->addData('project', $this->getProject());
		$this->addData('item', $this->getItem());

		return parent::render();
	}
}
