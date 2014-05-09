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
 * Controller class to save an article.
 *
 * @since  1.0
 */
class Save extends AbstractTrackerController
{
	/**
	 * The default view for the app.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $defaultView = 'article';

	/**
	 * Model object
	 *
	 * @var    \App\Text\Model\ArticleModel
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
		/* @type \JTracker\Application $application */
		$application = $this->getContainer()->get('app');

		$application->getUser()->authorize('admin');

		$data = $application->input->get('article', [], 'array');

		if (isset($data['textMd']) && $data['textMd'])
		{
			// Compile the markdown text using GitHub's markdown parser.
			$data['text'] = $this->getContainer()->get('gitHub')->markdown->render($data['textMd']);
		}

		try
		{
			$this->model->save($data);

			$application->enqueueMessage(g11n3t('The article has been saved.'), 'success')
				->redirect('/text');
		}
		catch (\InvalidArgumentException $exception)
		{
			// @todo "persist" entered values on error redirect

			$id = $application->input->getUint('id');

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
