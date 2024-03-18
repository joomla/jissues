<?php

/**
 * Part of the Joomla Tracker's GitHub Application
 *
 * @copyright  Copyright (C) 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\GitHub\Controller\Ajax\Milestones;

use JTracker\Controller\AbstractAjaxController;
use JTracker\Github\GithubFactory;

/**
 * Base class for milestones.
 *
 * @since  1.0
 */
class Base extends AbstractAjaxController
{
    /**
     * Get a list of milestones for a project.
     *
     * @param   \App\Projects\TrackerProject  $project  The project.
     *
     * @return  array
     *
     * @since   1.0
     */
    protected function getList($project)
    {
        $gitHub = GithubFactory::getInstance($this->getContainer()->get('app'));

        $data = array_merge(
            $gitHub->issues->milestones->getList($project->gh_user, $project->gh_project),
            $gitHub->issues->milestones->getList($project->gh_user, $project->gh_project, 'closed')
        );

        $milestones = [];

        foreach ($data as $item) {
            // This is to keep request data short..

            $milestone = new \stdClass();

            $milestone->number      = $item->number;
            $milestone->title       = $item->title;
            $milestone->state       = $item->state;
            $milestone->description = $item->description;
            $milestone->due_on      = $item->due_on;

            $milestones[] = $milestone;
        }

        // Sort milestones by their number
        usort(
            $milestones,
            function ($a, $b) {
                return $a->number > $b->number;
            }
        );

        return $milestones;
    }

    /**
     * Prepare the response.
     *
     * @return  void
     *
     * @since   1.0
     */
    protected function prepareResponse()
    {
    }
}
