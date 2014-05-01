<?php
/**
 * Part of the Joomla Tracker's Text Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Text\Controller\Article;

use JTracker\Controller\AbstractTrackerController;

/**
 * Controller class to delete an article.
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
		/* @type \JTracker\Application $application */
		$application = $this->getContainer()->get('app');

		$application->getUser()->authorize('admin');

		$entityManager = $this->getContainer()->get('EntityManager');

		$article = $entityManager->find('App\Text\Entity\Article', $application->input->getUint('id'));

		if (!$article)
		{
			throw new \UnexpectedValueException('Invalid article');
		}

		$entityManager->remove($article);
		$entityManager->flush();

		$application->enqueueMessage(g11n3t('The article has been deleted.'), 'success');

		$application->redirect('/text');

		return parent::execute();
	}
}
