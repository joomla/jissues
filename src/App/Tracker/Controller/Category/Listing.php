<?php
namespace App\Tracker\Controller\Category;

use App\Tracker\Model\CategoriesModel;
use JTracker\Controller\AbstractTrackerListController;
use App\Tracker\View\Category\CategoriesHtmlView;

class Listing extends AbstractTrackerListController{
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

	public function initialize(){
		parent::initialize();

		/* @type \JTracker\Application $application */
		$application = $this->getContainer()->get('app');
		$application->getUser()->authorize('admin');

		$this->model->setProject($this->getContainer()->get('app')->getProject(true));
//		print_r($this->model->getItems());
		$this->view->setProject($this->getContainer()->get('app')->getProject());

		return $this;
	}
}