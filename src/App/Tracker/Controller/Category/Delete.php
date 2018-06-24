<?php
/**
 * Part of the Joomla Tracker's Projects Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Controller\Category;

use App\Tracker\Model\CategoryModel;
use App\Tracker\View\Categories\CategoriesHtmlView;

use JTracker\Controller\AbstractTrackerController;

/**
 * Controller class to delete a project.
 *
 * @since  1.0
 */
class Delete extends AbstractTrackerController
{
	/**
	 * The default view for the component
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $defaultView = 'category';

	/**
	 * Model object
	 *
	 * @var    CategoryModel
	 * @since  1.0
	 */
	protected $model;

	/**
	 * View object
	 *
	 * @var    CategoriesHtmlView
	 * @since  1.0
	 */
	protected $view;

	/**
	 * Initialize the controller.
	 *
	 * This will set up default model and view classes.
	 *
	 * @return  $this  Method allows chaining
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	public function initialize()
	{
		parent::initialize();
		$this->getContainer()->get('app')->getUser()->authorize('manage');
		$this->model->setProject($this->getContainer()->get('app')->getProject());
		$this->view->setProject($this->getContainer()->get('app')->getProject());

		return $this;
	}

	/**
	 * Execute the controller.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		/** @var \JTracker\Application $application */
		$application = $this->getContainer()->get('app');

		try
		{
			$this->model->delete($application->input->get('id'));
			$application->enqueueMessage(g11n3t('The category has been deleted'), 'success');
		}
		catch (\Exception $exception)
		{
			$application->enqueueMessage($exception->getMessage(), 'error');
		}

		$application->redirect($application->get('uri.base.path') . 'category/' . $application->getProject()->alias);

		return parent::execute();
	}
}
