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
use JTracker\Container;

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
	 * Method to render the view.
	 *
	 * @return  string  The rendered view.
	 *
	 * @since   1.0
	 */
	public function render()
	{
		/* @type \JTracker\Application $application */
		$application = Container::retrieve('app');

		$id = $application->input->getInt('id');

		$item = $id
			? $this->model->getItem($id)
			: new ArticlesTable(Container::retrieve('db'));

		$this->renderer->set('item', $item);

		return parent::render();
	}
}
