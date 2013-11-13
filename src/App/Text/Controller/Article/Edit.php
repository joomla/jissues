<?php
/**
 * Part of the Joomla Tracker's Text Application
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Text\Controller\Article;

use App\Text\Model\ArticleModel;
use App\Text\View\Article\ArticleHtmlView;

use JTracker\Controller\AbstractTrackerController;

/**
 * Controller class to edit an article.
 *
 * @since  1.0
 */
class Edit extends AbstractTrackerController
{
	/**
	 * The default view for the component
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $defaultView = 'article';

	/**
	 * The default view for the component
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $defaultLayout = 'edit';

	/**
	 * @var  ArticleHtmlView
	 */
	protected $view;

	/**
	 * @var  ArticleModel
	 */
	protected $model;

	/**
	 * Initialize the controller.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	public function initialize()
	{
		parent::initialize();

		$this->container->get('app')->getUser()->authorize('admin');

		$this->view->setItem($this->model->getItem($this->container->get('app')->input->getInt('id')));
	}
}
