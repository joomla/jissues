<?php
/**
 * Part of the Joomla Tracker's Text Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Text\Controller;

use App\Text\Model\ArticlesModel;
use Joomla\Controller\AbstractController;
use Joomla\View\BaseHtmlView;
use JTracker\Controller\Concerns\HasLists;
use Laminas\Diactoros\Response\HtmlResponse;

/**
 * Articles list controller.
 *
 * @method  \JTracker\Application getApplication()
 *
 * @since  1.0
 */
class ListArticlesController extends AbstractController
{
	use HasLists;

	/**
	 * The articles model
	 *
	 * @var    ArticlesModel
	 * @since  1.0
	 */
	private $model;

	/**
	 * The articles HTML view
	 *
	 * @var    BaseHtmlView
	 * @since  1.0
	 */
	private $view;

	/**
	 * Controller constructor.
	 *
	 * @param   ArticlesModel  $model  The articles model
	 * @param   BaseHtmlView   $view   The articles HTML view
	 *
	 * @since   1.0
	 */
	public function __construct(ArticlesModel $model, BaseHtmlView $view)
	{
		$this->model = $model;
		$this->view  = $view;
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

		$this->configurePaginationState($this->getApplication(), $this->model);

		// Set view variables required in the template
		$this->view->addData('view', 'articles')
			->addData('layout', 'index')
			->addData('app', 'text');

		// Push articles into view
		$this->view->addData('items', $this->model->getItems());

		$this->getApplication()->setResponse(
			new HtmlResponse(
				$this->view->render()
			)
		);

		return true;
	}
}
