<?php

/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2026 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Controller\AiReview\Ajax;

use App\Projects\TrackerProject;
use App\Tracker\Model\AiReviewFindingModel;
use JTracker\Authentication\GitHub\GitHubUser;
use JTracker\Controller\AbstractAjaxController;

/**
 * Base class for the actions on AI review findings. Site admins only, POST only.
 *
 * @since  1.0
 */
abstract class AbstractFindingController extends AbstractAjaxController
{
    /**
     * The current project
     *
     * @var    TrackerProject
     * @since  1.0
     */
    protected $project;

    /**
     * The current user
     *
     * @var    GitHubUser
     * @since  1.0
     */
    protected $user;

    /**
     * The finding model for the current project
     *
     * @var    AiReviewFindingModel
     * @since  1.0
     */
    protected $findingModel;

    /**
     * Check the request and load the finding it is about.
     *
     * The finding must belong to the project from the request, be open, and its review must still be valid.
     *
     * @param   string[]  $kinds  The kinds of findings the action applies to. Empty for all kinds.
     *
     * @return  object  The finding with its review and item, see AiReviewFindingModel::getOpenFinding().
     *
     * @since   1.0
     * @throws  \Exception
     */
    protected function loadFinding(array $kinds = []): object
    {
        /** @var \JTracker\Application\Application $application */
        $application = $this->getContainer()->get('app');

        $this->project = $application->getProject();
        $this->user    = $application->getUser();

        if (!$this->user->id || !$this->user->check('admin')) {
            throw new \Exception('You are not allowed to process AI review findings.');
        }

        if ($application->getInput()->getMethod() !== 'POST') {
            throw new \Exception('This action requires a POST request.');
        }

        $findingId = $application->getInput()->getUint('findingId');

        if (!$findingId) {
            throw new \Exception('No finding ID received.');
        }

        $this->findingModel = (new AiReviewFindingModel($this->getContainer()->get('db')))
            ->setProject($this->project);

        $finding = $this->findingModel->getOpenFinding($findingId);

        if ($kinds && !\in_array($finding->kind, $kinds, true)) {
            throw new \UnexpectedValueException('This action is not available for this finding.');
        }

        return $finding;
    }
}
