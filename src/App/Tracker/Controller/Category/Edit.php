<?php
/**
 * Part of the Joomla Tracker's Projects Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Controller\Category;


use JTracker\Controller\AbstractTrackerController;
use App\Tracker\View\Category\CategoryHtmlView;
use App\Tracker\Model\CategoryModel;

/**
 * Controller class to edit an item of the category
 *
 * @since  1.0
 */
class Edit extends AbstractTrackerController
{
	/**
	 * The default view for the component.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $defaultView = 'category';

	/**
	 * The default layout for the component.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $defaultLayout = 'edit';

	/**
	 * View object
	 *
	 * @var    CategoryHtmlView
	 * @since  1.0
	 */
	protected $view = null;

	/**
	 * Model object
	 *
	 * @var    CategoryModel
	 * @since  1.0
	 */
	protected $model = null;

	/**
	 * Execute the controller.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		/* @type \JTracker\Application $application */
		$application = $this->getContainer()->get('app');
		$application->getUser()->authorize('manage');

		$item = $this->model->getItem($application->input->getUint('id'));
		$this->view->setProject($application->getProject());
		$this->model->setProject($application->getProject());
		$this->view->setItem($item);

		return parent::execute();
	}
}
