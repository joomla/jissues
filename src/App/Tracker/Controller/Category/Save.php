<?php
/**
 * Part of the Joomla Tracker's Projects Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Controller\Category;


use JTracker\Controller\AbstractTrackerController;
use App\Tracker\Model\CategoryModel;
use App\Tracker\Table\CategoryTable;

/**
 * Controller class to save an item to the categories.
 *
 * @since  1.0
 */
class Save extends AbstractTrackerController
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

		$app->getUser()->authorize('manage');
		$project = $app->getProject();

		try
		{
			$this->model->setProject($project);
			$data = $app->input->get('category', array(), 'array');

			if (isset($data['id']))
			{
				$this->model->save($data);
			}
			else
			{
				$this->model->add($data);
			}

			// Reload the project.
			$this->model->setProject($project);

			$app->enqueueMessage('The changes have been saved.', 'success');
			$app->redirect($app->get('uri.base.path') . 'category/' . $project->alias);
		}
		catch (\Exception $exception)
		{
			$app->enqueueMessage($exception->getMessage(), 'error');

			if ($app->input->get('id'))
			{
				$app->redirect($app->get('uri.base.path') . 'category/' . $project->alias . '/' . $app->input->get('id') . '/edit');
			}
			else
			{
				$app->redirect($app->get('uri.base.path') . 'category/' . $project->alias . '/add');
			}
		}

		parent::execute();
	}
}
