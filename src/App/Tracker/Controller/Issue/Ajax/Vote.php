<?php

/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Controller\Issue\Ajax;

use App\Tracker\Model\IssueModel;
use Joomla\Input\Input;
use JTracker\Controller\AbstractAjaxController;

/**
 * Add comments controller class.
 *
 * @since  1.0
 */
class Vote extends AbstractAjaxController
{
    /**
     * Prepare the response.
     *
     * @return  mixed
     *
     * @since   1.0
     * @throws  \Exception
     */
    protected function prepareResponse()
    {
        /** @var \JTracker\Application\Application $application */
        $application = $this->getContainer()->get('app');

        // The project comes from the route, load it first so the permissions are checked against it.
        $project = $application->getProject();

        // Verify the user has permissions to perform this action
        if (!$application->getUser()->authorize('view')) {
            throw new \Exception('Anonymous votes are not allowed.');
        }

        /** @var Input $input */
        $input = $application->getInput();

        $issue       = $input->getUint('issueId');
        $experienced = $input->getInt('experienced');
        $importance  = $input->getInt('importance');
        $userID      = $application->getUser()->id;

        if (!$issue) {
            throw new \Exception('No issue ID received.');
        }

        if (!$importance) {
            throw new \Exception('Issue importance not received');
        }

        $issueModel = new IssueModel($this->getContainer()->get('db'));

        // Make sure the issue belongs to the project from the request.
        if (!$issueModel->getIssueNumberById($issue, $project->project_id)) {
            throw new \Exception('Invalid issue ID.');
        }

        $data = $issueModel->vote($issue, $experienced, $importance, $userID);

        // Add the new score
        $data->importanceScore = $data->score / $data->votes;

        $this->response->data    = $data;
        $this->response->message = 'Vote successfully added';
    }
}
