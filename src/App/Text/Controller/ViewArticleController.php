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
use JTracker\View\BaseHtmlView;
use Laminas\Diactoros\Response\HtmlResponse;

/**
 * Page view controller.
 *
 * @method  \JTracker\Application getApplication()
 *
 * @since   1.0
 */
class ViewArticleController extends AbstractController
{
	/**
	 * The articles model
	 *
	 * @var    ArticlesModel
	 * @since  1.0
	 */
	private $model;

	/**
	 * The page HTML view
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
		// Set view variables required in the template
		$this->view->addData('view', 'page')
			->addData('layout', 'index')
			->addData('app', 'text');

		// Push page into view
		// TODO - Twig doesn't use __get to read properties
		$this->view->addData('item', $this->model->findByAlias($this->getInput()->getCmd('alias'))->getIterator());

		$this->getApplication()->setResponse(
			new HtmlResponse(
				$this->view->render()
			)
		);

		return true;
	}
}
