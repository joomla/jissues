<?php
/**
 * Created by PhpStorm.
 * User: allenzhao
 * Date: 7/10/14
 * Time: 10:25 PM
 */

namespace App\Tracker\Controller\Category;


use JTracker\Controller\AbstractTrackerController;
use App\Tracker\Model\CategoryModel;
use App\Tracker\Table\CategoryTable;

class Save extends AbstractTrackerController
{

	/**
	 * The default view for the component
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $defaultView = 'categories';
	/**
	 * Model object
	 *
	 * @var    CategoryModel
	 * @since  1.0
	 */
	protected $model;

	/**
	 * Execute the controller.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		/* @type \JTracker\Application $app */
		$app = $this->getContainer()->get('app');

		$app->getUser()->authorize('admin');
		$project = $app->getProject();

		$table = new CategoryTable($this->getContainer()->get('db'));
		try{
			$table->save($app->input->get('category', array(), 'array'));
			// Reload the project.
			$this->model->setProject($project);

			$app->enqueueMessage('The changes have been saved.', 'success');
			$app->redirect($app->get('uri.base.path').'category/'.$project->alias);
		}catch (\Exception $exception){
			$app->enqueueMessage($exception,'error');
			$app->redirect($app->get('uri.base.path').'category/'.$project->alias.'/'.$app->input->get('id').'/edit');
		}
		parent::execute();
	}
}