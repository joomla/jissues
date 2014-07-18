<?php
/**
 * Part of the Joomla Tracker Controller Package
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Controller;

use Joomla\Uri\Uri;

use JTracker\Application;
use JTracker\Model\AbstractDoctrineListModel;
use JTracker\Pagination\TrackerPagination;
use JTracker\View\AbstractDoctrineHtmlListView;

/**
 * Abstract Controller class for the Tracker Application
 *
 * @since  1.0
 */
abstract class AbstractDoctrineListController extends AbstractTrackerController
{
	/**
	 * View object
	 *
	 * @var    AbstractDoctrineHtmlListView
	 * @since  1.0
	 */
	protected $view;

	/**
	 * Model object
	 *
	 * @var  AbstractDoctrineListModel
	 * @since  1.0
	 */
	protected $model;

	/**
	 * Initialize the controller.
	 *
	 * @return  $this  Method allows chaining
	 *
	 * @since   1.0
	 */
	public function initialize()
	{
		parent::initialize();

		// NOTE: No ACL here (since it's project based..) !
		// $this->getContainer()->get('app')->getUser()->authorize('view');

		/* @type Application $application */
		$application = $this->getContainer()->get('app');

		$page = $application->input->getInt('page');

		$maxResults = $application->get('system.list_limit');

		$firstResult = $page ? ($page - 1) * $maxResults : 0;

		$paginator = $this->model->getPaginator($firstResult, $maxResults);

		$pagination = new TrackerPagination(new Uri($application->get('uri.request')));

		$pagination->setValues(count($paginator), $firstResult, $maxResults);

		$this->view->setPaginator($paginator);
		$this->view->setPagination($pagination);

		return $this;
	}
}
