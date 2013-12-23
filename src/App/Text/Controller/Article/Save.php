<?php
/**
 * Part of the Joomla Tracker's Text Application
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Text\Controller\Article;

use App\Text\Table\ArticlesTable;

use JTracker\Controller\AbstractTrackerController;

/**
 * Controller class to save an article.
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
		$app = $this->container->get('app');

		$app->getUser()->authorize('admin');

		$table = new ArticlesTable($this->container->get('db'));

		/* @type \Joomla\Github\Github $gitHub */
		$table->setGitHub($this->container->get('gitHub'));

		$table->save($app->input->get('article', array(), 'array'));

		$app->enqueueMessage(g11n3t('The article has been saved.'), 'success');

		parent::execute();
	}
}
