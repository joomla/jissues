<?php
/**
 * Part of the Joomla Tracker's Text Application
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Text\Controller\Article;

use App\Text\Table\ArticlesTable;
use App\Tracker\Controller\DefaultController;
use JTracker\Container;

/**
 * Controller class to save an article.
 *
 * @since  1.0
 */
class Save extends DefaultController
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

		$table = new ArticlesTable(Container::retrieve('db'));

		$table->save($app->input->get('article', array(), 'array'));

		$app->enqueueMessage(g11n3t('The article has been saved.'), 'success');

		$this->getInput()->set('view', 'articles');

		parent::execute();
	}
}
