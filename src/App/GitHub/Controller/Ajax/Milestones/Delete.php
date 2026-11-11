<?php

/**
 * Part of the Joomla Tracker's GitHub Application
 *
 * @copyright  Copyright (C) 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\GitHub\Controller\Ajax\Milestones;

use JTracker\Github\GithubFactory;

/**
 * Controller class to delete milestones from the GitHub repository.
 *
 * @since  1.0
 */
class Delete extends Base
{
    /**
     * Prepare the response.
     *
     * @return  void
     *
     * @since   1.0
     */
    protected function prepareResponse()
    {
        /** @var \JTracker\Application\Application $application */
        $application = $this->getContainer()->get('app');

        $application->getUser()->authorize('manage');

        $milestoneId = $application->getInput()->getUint('milestone_id');

        $project = $application->getProject();

        // Use the bot account of the project if one is configured.
        $gitHub = GithubFactory::getProjectBotInstance($application, $project);

        // Delete the milestone
        $gitHub->issues->milestones->delete($project->gh_user, $project->gh_project, $milestoneId);

        // Get the current milestones list.
        $this->response->data = $this->getList($project);
    }
}
