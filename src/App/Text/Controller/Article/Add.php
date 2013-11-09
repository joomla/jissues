<?php
/**
 * Part of the Joomla Tracker's Text Application
 *
 * @copyright  Copyright (C) 2013 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Text\Controller\Article;

use App\Text\Table\ArticlesTable;
use App\Text\View\Article\ArticleHtmlView;
use Joomla\Input\Input;
use JTracker\Controller\AbstractTrackerController;
use JTracker\View\AbstractTrackerHtmlView;

/**
 * Controller class to add an article.
 *
 * @since  1.0
 */
class Add extends AbstractTrackerController
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
	 * Execute the controller.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		$this->container->get('app')->getUser()->authorize('admin');

		$this->container->get('app')->input->set('layout', 'edit');

		parent::execute();
	}

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

		$this->view->setItem(new ArticlesTable($this->container->get('db')));
	}


}
