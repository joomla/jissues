<?php
/**
 * Part of the Joomla Tracker's Text Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
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
	 * @return  string
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		$app = $this->getContainer()->get('app');

		$app->getUser()->authorize('admin');

		$table = new ArticlesTable($this->getContainer()->get('db'));

		/* @type \Joomla\Github\Github $gitHub */
		$table->setGitHub($this->getContainer()->get('gitHub'));

		$table->save($app->input->get('article', array(), 'array'));

		$app->enqueueMessage(g11n3t('The article has been saved.'), 'success');

		return parent::execute();
	}
}
