<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Tracker\View\Categories;

use App\Tracker\Model\CategoriesModel;
use JTracker\View\AbstractTrackerHtmlView;

/**
 * The category list view
 *
 * @since  1.0
 */
class CategoriesHtmlView extends AbstractTrackerHtmlView
{
	/**
	 * Redefine the model so the correct type hinting is available.
	 *
	 * @var     CategoriesModel
	 * @since   1.0
	 */
	protected $model;

	/**
	 * Method to render the view.
	 *
	 * @return  string  The rendered view.
	 *
	 * @throws  \RuntimeException
	 * @since   1.0
	 */
	public function render()
	{
		// Set the vars to the template.
		$this->addData('items', $this->model->getItems());
		$this->addData('pagination', $this->model->getPagination());
		$this->addData('project', $this->getProject());

		return parent::render();
	}
}
