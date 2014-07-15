<?php
/**
 * Created by PhpStorm.
 * User: allenzhao
 * Date: 7/10/14
 * Time: 11:41 AM
 */

namespace App\Tracker\Controller\Category;


use JTracker\Controller\AbstractTrackerController;
use App\Tracker\View\Category\CategoryHtmlView;
use App\Tracker\Model\CategoryModel;

class Edit extends AbstractTrackerController{
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
		$application->getUser()->authorize('admin');
		$item = $this->model->getItem($application->input->getUint('id'));
		$this->view->setProject($application->getProject());
		$this->model->setProject($application->getProject());
		$this->view->setItem($item);

		return parent::execute();
	}
} 