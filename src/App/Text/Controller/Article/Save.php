<?php
/**
 * Part of the Joomla Tracker's Text Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Text\Controller\Article;

use App\Text\Entity\Article;

use JTracker\Controller\AbstractTrackerController;

/**
 * Controller class to save an article.
 *
 * @since  1.0
 */
class Save extends AbstractTrackerController
{
	/**
	 * The default view for the component.
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

		$input = $application->input;

		$entityManager = $this->getContainer()->get('EntityManager');

		$id = $input->getUint('id');

		if (!$id)
		{
			$article = new Article;
		}
		else
		{
			$article = $entityManager->find('App\Text\Entity\Article', $id);
		}

		try
		{
			$article
				->setTitle($input->getString('title'))
				->setAlias($input->getCmd('alias'))
				->setTextMd($input->getString('text'))
				->setText($this->getContainer()->get('gitHub')->markdown->render($input->getString('text')));

			$entityManager->persist($article);
			$entityManager->flush();

			$application->enqueueMessage(g11n3t('The article has been saved.'), 'success')
				->redirect('/text');
		}
		catch (\InvalidArgumentException $exception)
		{
			// @todo "persist" entered values on error redirect

			$application->enqueueMessage($exception->getMessage(), 'error');

			if ($id)
			{
				$application->redirect('/text/edit/' . $id);
			}
			else
			{
				$application->redirect('/text/add');
			}
		}

		return parent::execute();
	}
}
