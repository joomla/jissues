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
use Laminas\Diactoros\Response\RedirectResponse;

/**
 * Controller class to delete an article.
 *
 * @method  \JTracker\Application\Application getApplication()
 *
 * @since  1.0
 */
class DeleteArticleController extends AbstractController
{
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
	 * @param   ArticlesModel  $model  The articles model
	 *
	 * @since   1.0
	 */
	public function __construct(ArticlesModel $model)
	{
		$this->model = $model;
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

		$this->model->delete($this->getInput()->getUint('id'));

		$this->getApplication()->enqueueMessage('The article has been deleted.', 'success');

		$this->getApplication()->setResponse(
			new RedirectResponse($this->getApplication()->get('uri.base.path') . 'articles')
		);

		return true;
	}
}
