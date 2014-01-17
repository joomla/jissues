<?php
/**
 * Part of the Joomla Tracker's Text Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Text\Controller\Article;

use App\Text\Table\ArticlesTable;
use App\Text\View\Article\ArticleHtmlView;

use JTracker\Controller\AbstractTrackerController;

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
	 * The default view for the component
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $defaultLayout = 'edit';

	/**
	 * View object
	 *
	 * @var    ArticleHtmlView
	 * @since  1.0
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

		parent::execute();
	}

	/**
	 * Initialize the controller.
	 *
	 * This will set up default model and view classes.
	 *
	 * @return  $this  Method supports chaining
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
