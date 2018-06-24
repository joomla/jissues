<?php
/**
 * Part of the Joomla Tracker's Projects Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Controller\Category;

use App\Tracker\Model\CategoriesModel;
use App\Tracker\View\Categories\CategoriesHtmlView;

use JTracker\Controller\AbstractTrackerListController;

/**
 * List controller class for category.
 *
 * @since  1.0
 */
class Listing extends AbstractTrackerListController
{
	/**
	 * View object
	 *
	 * @var   CategoriesHtmlView
	 * @since  1.0
	 */
	protected $view;

	/**
	 * The default view for the app
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $defaultView = 'categories';

	/**
	 * Model object
	 *
	 * @var    CategoriesModel
	 * @since  1.0
	 */
	protected $model;

	/**
	 * Initialize the controller.
	 *
	 * @return  $this  Method supports chaining
	 *
	 * @since   1.0
	 */
	public function initialize()
	{
		parent::initialize();

		/** @var \JTracker\Application $application */
		$application = $this->getContainer()->get('app');
		$application->getUser()->authorize('manage');

		$this->model->setProject($this->getContainer()->get('app')->getProject(true));
		$this->view->setProject($this->getContainer()->get('app')->getProject());

		return $this;
	}
}
