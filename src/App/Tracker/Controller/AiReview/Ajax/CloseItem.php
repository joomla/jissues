<?php

/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2026 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Controller\AiReview\Ajax;

use App\Tracker\Model\AiReviewFindingModel;
use App\Tracker\Table\AiReviewFindingsTable;
use Joomla\Http\Exception\UnexpectedResponseException;
use JTracker\Helper\GitHubHelper;

/**
 * Confirm a duplicate or a fix elsewhere: comment on the item, close it, and record the status and the relation.
 * Site admins only.
 *
 * The comment and the close are made with the admin's own GitHub token.
 *
 * @since  1.0
 */
class CloseItem extends AbstractFindingController
{
    /**
     * Prepare the response.
     *
     * @return  void
     *
     * @since   1.0
     * @throws  \Exception
     */
    protected function prepareResponse()
    {
        $finding = $this->loadFinding([AiReviewFindingsTable::KIND_DUPLICATE, AiReviewFindingsTable::KIND_FIXED_ELSEWHERE]);

        if (!$finding->related_number) {
            throw new \UnexpectedValueException('The finding does not name the other item.');
        }

        /** @var \JTracker\Application\Application $application */
        $application = $this->getContainer()->get('app');

        $text = trim((string) $application->getInput()->get('text', '', 'raw'));

        if ($text === '') {
            throw new \UnexpectedValueException('The comment is empty.');
        }

        $gitHub       = $this->getUserGitHub();
        $gitHubHelper = new GitHubHelper($gitHub);
        $issueNumber  = (int) $finding->issue_number;

        try {
            $gitHubHelper->addComment(
                $this->project,
                $issueNumber,
                $text . $gitHubHelper->getApplicationComment($application, $this->project, $issueNumber),
                $this->user->username,
                $this->getContainer()->get('db')
            );
        } catch (UnexpectedResponseException | \DomainException $exception) {
            // Only GitHub errors; a database error after the comment was posted must not claim that nothing changed.
            throw $this->gitHubError($exception);
        }

        try {
            $gitHub->issues->edit($this->project->gh_user, $this->project->gh_project, $issueNumber, 'closed');
        } catch (\Exception $exception) {
            throw $this->gitHubError($exception, 'The comment was posted, but the item is still open. Close it on GitHub by hand.');
        }

        $close = $finding->kind === AiReviewFindingsTable::KIND_DUPLICATE
            ? AiReviewFindingModel::CLOSE_DUPLICATE
            : AiReviewFindingModel::CLOSE_FIXED_ELSEWHERE;

        $this->findingModel->closeItem($finding, $close, $this->user->username);
        $this->findingModel->process((int) $finding->id, AiReviewFindingsTable::STATE_ACCEPTED, $this->user->username, $text);

        $this->response->data    = (object) ['findingId' => (int) $finding->id, 'state' => AiReviewFindingsTable::STATE_ACCEPTED];
        $this->response->message = \sprintf('#%d was commented on and closed.', $issueNumber);
    }
}
