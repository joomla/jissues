<?php
/**
 * Part of the Joomla Tracker's Text Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Text\Controller;

use App\Text\Model\ArticlesModel;
use Joomla\Controller\AbstractController;
use Joomla\GitHub\Github;
use Laminas\Diactoros\Response\RedirectResponse;

/**
 * Controller class to save an article.
 *
 * @method  \JTracker\Application getApplication()
 *
 * @since  1.0
 */
class SaveArticleController extends AbstractController
{
	/**
	 * GitHub API connector
	 *
	 * @var    Github
	 * @since  1.0
	 */
	private $github;

	/**
	 * The articles model
	 *
	 * @var    ArticlesModel
	 * @since  1.0
	 */
	private $model;

	/**
	 * Controller constructor.
	 *
	 * @param   ArticlesModel  $model   The articles model
	 * @param   Github         $github  GitHub API connector
	 *
	 * @since   1.0
	 */
	public function __construct(ArticlesModel $model, Github $github)
	{
		$this->model  = $model;
		$this->github = $github;
	}

	/**
	 * Execute the controller.
	 *
	 * @return  boolean
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		$this->getApplication()->getUser()->authorize('admin');

		$data = [
			'article_id' => $this->getInput()->getUint('id'),
			'title'      => $this->getInput()->getString('title'),
			'alias'      => $this->getInput()->getString('alias'),
			'text_md'    => $this->getInput()->get('text_md', null, 'raw'),
		];

		$data['text'] = $this->github->markdown->render($data['text_md']);

		$this->model->save($data);

		$this->getApplication()->enqueueMessage('The article has been saved.', 'success');

		$this->getApplication()->setResponse(
			new RedirectResponse($this->getApplication()->get('uri.base.path') . 'articles')
		);

		return true;
	}
}
