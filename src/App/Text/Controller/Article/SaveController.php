<?php
/**
 * @copyright  Copyright (C) 2013 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Text\Controller\Article;

use App\Text\Table\ArticlesTable;
use App\Tracker\Controller\DefaultController;

/**
 * Controller class to save a project.
 *
 * @since  1.0
 */
class SaveController extends DefaultController
{
	/**
	 * The default view for the component
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $defaultView = 'articles';

	/**
	 * Execute the controller.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		$app = $this->getApplication();

		$app->getUser()->authorize('admin');

		$table = new ArticlesTable($app->getDatabase());

		$table->save($app->input->get('article', array(), 'array'));

		$this->getInput()->set('view', 'articles');

		parent::execute();
	}
}
