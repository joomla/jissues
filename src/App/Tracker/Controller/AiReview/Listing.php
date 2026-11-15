<?php

/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2026 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Controller\AiReview;

use App\Tracker\Model\AireviewsModel;
use App\Tracker\View\Aireviews\AireviewsHtmlView;
use JTracker\Controller\AbstractTrackerListController;

/**
 * List controller for the findings of imported AI reviews. Site admins only.
 *
 * @since  1.0
 */
class Listing extends AbstractTrackerListController
{
    /**
     * View object
     *
     * @var    AireviewsHtmlView
     * @since  1.0
     */
    protected $view;

    /**
     * The default view for the app
     *
     * @var    string
     * @since  1.0
     */
    protected $defaultView = 'aireviews';

    /**
     * Model object
     *
     * @var    AireviewsModel
     * @since  1.0
     */
    protected $model;

    /**
     * The filters of the list and their default values
     */
    public const FILTERS = [
        'state'      => 'open',
        'kind'       => '',
        'confidence' => '',
        'rating'     => '',
        'type'       => '',
        'search'     => '',
    ];

    /**
     * Initialize the controller.
     *
     * @return  $this  Method supports chaining
     *
     * @since   1.0
     */
    public function initialize()
    {
        /** @var \JTracker\Application\Application $application */
        $application = $this->getContainer()->get('app');
        $application->getUser()->authorize('admin');

        parent::initialize();

        $project = $application->getProject(true);

        $this->model->setProject($project);
        $this->view->setProject($project);

        $state   = $this->model->getState();
        $filters = [];

        foreach (self::FILTERS as $name => $default) {
            $value = $application->getUserStateFromRequest(
                'project_' . $project->project_id . '.aireviews.filter.' . $name,
                $name,
                $default,
                'string'
            );

            $state->set('filter.' . $name, $value);
            $filters[$name] = $value;
        }

        $this->view->setFilters($filters);

        return $this;
    }
}
