<?php
/**
 * Part of the Joomla Tracker's Text Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Text\Controller;

use JTracker\Controller\AbstractTrackerController;

/**
 * Controller class for the Text component.
 *
 * @since  1.0
 */
class Articles extends AbstractTrackerController
{
	/**
	 * View object
	 *
	 * @var    \App\Text\View\Articles\ArticlesHtmlView
	 * @since  1.0
	 */
	protected $view;

	/**
	 * Model object
	 *
	 * @var    \App\Text\Model\ArticlesModel
	 * @since  1.0
	 */
	protected $model;

	/**
	 * Execute the controller.
	 *
	 * @return  string  The rendered view.
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		$this->getContainer()->get('app')->getUser()->authorize('admin');

		$this->view->setItems($this->model->getItems());

		return parent::execute();
	}
}
