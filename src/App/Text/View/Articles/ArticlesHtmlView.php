<?php
/**
 * Part of the Joomla Tracker's Text Application
 *
 * @copyright  Copyright (C) 2012-2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Text\View\Articles;

use App\Text\Model\ArticlesModel;
use JTracker\View\AbstractTrackerHtmlView;

/**
 * Articles view class
 *
 * @since  1.0
 */
class ArticlesHtmlView extends AbstractTrackerHtmlView
{
	/**
	 * Redefine the model so the correct type hinting is available.
	 *
	 * @var    ArticlesModel
	 * @since  1.0
	 */
	protected $model;

	/**
	 * Method to render the view.
	 *
	 * @return  string  The rendered view.
	 *
	 * @since   1.0
	 */
	public function render()
	{
		$this->addData('items', $this->model->getItems());

		return parent::render();
	}
}
