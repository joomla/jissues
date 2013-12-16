<?php
/**
 * Part of the Joomla Tracker Controller Package
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JTracker\Controller;

use App\Tracker\Model\IssuesModel;

use Joomla\Registry\Registry;
use Joomla\Uri\Uri;

use JTracker\Application;
use JTracker\Pagination\TrackerPagination;

/**
 * Abstract Controller class for the Tracker Application
 *
 * @since  1.0
 */
abstract class AbstractTrackerListController extends AbstractTrackerController
{
	/**
	 * @var TrackerPagination
	 */
	protected $pagination;

	/**
	 * @var IssuesModel
	 */
	protected $model;

	/**
	 * Initialize the controller.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	public function initialize()
	{
		parent::initialize();

		/* @type Application $application */
		$application = $this->container->get('app');

		$limit = $application->getUserStateFromRequest('list.limit', 'list_limit', 20, 'int');
		$page  = $this->container->get('app')->input->getInt('page');

		$value = $page ? ($page - 1) * $limit : 0;
		$limitStart = ($limit != 0 ? (floor($value / $limit) * $limit) : 0);

		$state = $this->model->getState();

		$state->set('list.start', $limitStart);
		$state->set('list.limit', $limit);

		$this->model->setState($state);

		$this->model->setPagination(new TrackerPagination(new Uri($this->container->get('app')->get('uri.request'))));

		return $this;
	}
}
