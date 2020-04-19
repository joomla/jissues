<?php
/**
 * Part of the Joomla Tracker's Text Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Text\Controller\Article;

use App\Text\Table\ArticlesTable;
use Joomla\Controller\AbstractController;
use Joomla\Database\DatabaseDriver;
use Joomla\View\BaseHtmlView;
use Laminas\Diactoros\Response\HtmlResponse;

/**
 * Controller class to add an article.
 *
 * @method  \JTracker\Application getApplication()
 *
 * @since  1.0
 */
class Add extends AbstractController
{
	/**
	 * The add article HTML view
	 *
	 * @var    BaseHtmlView
	 * @since  1.0
	 */
	private $view;

	/**
	 * Database driver
	 *
	 * @var    DatabaseDriver
	 * @since  1.0
	 */
	private $db;

	/**
	 * Controller constructor.
	 *
	 * @param   BaseHtmlView    $view  The add article HTML view
	 * @param   DatabaseDriver  $db    Database driver
	 *
	 * @since   1.0
	 */
	public function __construct(BaseHtmlView $view, DatabaseDriver $db)
	{
		$this->view = $view;
		$this->db   = $db;
	}

	/**
	 * Execute the controller.
	 *
	 * @return  boolean
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		$this->getApplication()->getUser()->authorize('admin');

		// Set view variables required in the template
		$this->view->addData('view', 'article')
			->addData('layout', 'edit')
			->addData('app', 'text');

		// Push an empty table object into the view
		$this->view->addData('item', new ArticlesTable($this->db));

		$this->getApplication()->setResponse(
			new HtmlResponse(
				$this->view->render()
			)
		);

		return true;
	}
}
