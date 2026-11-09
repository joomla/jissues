<?php

/**
 * Part of the Joomla Tracker's GitHub Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\GitHub\Controller;

use App\GitHub\View\Stats\StatsHtmlView;
use Joomla\Github\Github;
use JTracker\Controller\AbstractTrackerController;

/**
 * Controller class to display project statistics
 *
 * @since  1.0
 */
class Stats extends AbstractTrackerController
{
    /**
     * View object
     *
     * @var    StatsHtmlView
     * @since  1.0
     */
    protected $view;

    /**
     * Initialize the controller.
     *
     * @return  $this  Method allows chaining
     *
     * @since   1.0
     * @throws  \RuntimeException
     */
    public function initialize()
    {
        parent::initialize();

        /** @var \JTracker\Application\Application $application */
        $application = $this->getContainer()->get('app');

        // Load the project from the request (if any) and make it the active one, so the access check applies to it.
        $project = $application->getProject((bool) $application->getInput()->get('project_alias'));

        // Only users with view access to the project may see its statistics.
        $application->getUser()->authorize('view');

        $data = (new Github())->repositories->statistics->getListContributors(
            $project->gh_user,
            $project->gh_project
        );

        $data = array_reverse($data);

        $this->view->setProject($project);
        $this->view->setContributors($data);

        return $this;
    }
}
