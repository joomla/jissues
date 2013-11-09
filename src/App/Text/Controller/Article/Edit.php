<?php
/**
 * Part of the Joomla Tracker's Text Application
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Text\Controller\Article;

use App\Text\View\Article\ArticleHtmlView;
use App\Tracker\Controller\DefaultController;

/**
 * Controller class to edit an article.
 *
 * @since  1.0
 */
class Edit extends DefaultController
{
	/**
	 * The default view for the component
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $defaultView = 'article';

	/**
	 * @var  ArticleHtmlView
	 */
	protected $view;

	/**
	 * Initialize the controller.
	 *
	 * This will set up default model and view classes.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	public function initialize()
	{
		parent::initialize();

		$id = $this->container->get('app')->input>getInt('id');

		$this->view->setItem($this->model->getItem($id));
	}

	/**
	 * Execute the controller.
	 *
	 * @return  string  The rendered view.
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		$this->container->get('app')->getUser()->authorize('admin');

		$input = $this->container->get('app')->input;

		$input->set('layout', 'edit');
		$input->set('view', 'article');

		return parent::execute();
	}
}
