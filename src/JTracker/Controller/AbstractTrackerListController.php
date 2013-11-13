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

		$this->container->get('app')->getUser()->authorize('view');

		$this->pagination = new TrackerPagination(new Uri($this->container->get('app')->get('uri.request')));

		$projectId = $application->getProject()->project_id;

		$state = new Registry;

		$state->set('filter.project', $projectId);

		$sort = $application->getUserStateFromRequest('project_' . $projectId . '.filter.sort', 'filter-sort', 0, 'uint');

		// $sort = $this->input->get('project_' . $projectId . '.filter.sort', 'filter-sort', 0, 'uint');

		switch ($sort)
		{
			case 1:
				$state->set('list.ordering', 'a.issue_number');
				$state->set('list.direction', 'ASC');
				break;

			case 2:
				$state->set('list.ordering', 'a.modified_date');
				$state->set('list.direction', 'DESC');
				break;

			case 3:
				$state->set('list.ordering', 'a.modified_date');
				$state->set('list.direction', 'ASC');
				break;

			default:
				$state->set('list.ordering', 'a.issue_number');
				$state->set('list.direction', 'DESC');
		}

		$state->set('filter.sort', $sort);

		$priority = $application->getUserStateFromRequest('project_' . $projectId . '.filter.priority', 'filter-priority', 0, 'uint');
		$state->set('filter.priority', $priority);

		$status = $application->getUserStateFromRequest('project_' . $projectId . '.filter.status', 'filter-status', 1, 'uint');
		$state->set('filter.status', $status);

		$search = $application->getUserStateFromRequest('project_' . $projectId . '.filter.search', 'filter-search', '', 'string');
		$state->set('filter.search', $search);

		// @todo huge change here - no more session state...
		$limit = $application->getUserStateFromRequest('list.limit', 'list_limit', 20, 'int');
		$page  = $this->container->get('app')->input->getInt('page');

		$value = $page ? ($page - 1) * $limit : 0;
		$limitStart = ($limit != 0 ? (floor($value / $limit) * $limit) : 0);

		$state->set('list.start', $limitStart);
		$state->set('list.limit', $limit);

		$this->model->setState($state);

		$this->model->setPagination(new TrackerPagination(new Uri($this->container->get('app')->get('uri.request'))));

		return $this;
	}
}
