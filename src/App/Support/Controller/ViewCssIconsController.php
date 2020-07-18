<?php
/**
 * Part of the Joomla Tracker's Support Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Support\Controller;

use App\Support\Model\IconsModel;
use Joomla\Controller\AbstractController;
use Joomla\View\BaseHtmlView;
use Laminas\Diactoros\Response\HtmlResponse;

/**
 * CSS icon list controller.
 *
 * @method  \JTracker\Application getApplication()
 *
 * @since   1.0
 */
class ViewCssIconsController extends AbstractController
{
	/**
	 * The icons model
	 *
	 * @var    IconsModel
	 * @since  1.0
	 */
	private $model;

	/**
	 * The icons HTML view
	 *
	 * @var    BaseHtmlView
	 * @since  1.0
	 */
	private $view;

	/**
	 * Controller constructor.
	 *
	 * @param   IconsModel    $model  The icons model
	 * @param   BaseHtmlView  $view   The icons HTML view
	 *
	 * @since   1.0
	 */
	public function __construct(IconsModel $model, BaseHtmlView $view)
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

		// Set view variables required in the template
		$this->view->addData('view', 'icons')
			->addData('layout', 'index')
			->addData('app', 'support');

		// Push icons into view
		$this->view->addData('icons', $this->model->getJoomlaIcons());
		$this->view->addData('octicons', $this->model->getOcticons());

		$this->getApplication()->setResponse(
			new HtmlResponse(
				$this->view->render()
			)
		);

		return true;
	}
}
