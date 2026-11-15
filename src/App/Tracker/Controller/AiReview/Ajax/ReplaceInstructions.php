<?php

/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2026 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Controller\AiReview\Ajax;

use App\Tracker\Helper\DescriptionSectionsHelper;
use App\Tracker\Table\AiReviewFindingsTable;
use App\Tracker\Table\AiReviewsTable;

/**
 * Replace the testing instructions in a pull request description with the text an admin approved. Site admins only.
 *
 * The change is made with the admin's own GitHub token. If GitHub refuses it, nothing is changed.
 *
 * @since  1.0
 */
class ReplaceInstructions extends AbstractFindingController
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
        $finding = $this->loadFinding([AiReviewFindingsTable::KIND_TESTING_INSTRUCTIONS]);

        if (!$finding->has_code) {
            throw new \UnexpectedValueException('Testing instructions can only be replaced in pull requests.');
        }

        $text = trim((string) $this->getContainer()->get('app')->getInput()->get('text', '', 'raw'));

        if ($text === '') {
            throw new \UnexpectedValueException('The testing instructions are empty.');
        }

        $gitHub = $this->getUserGitHub();
        $owner  = $this->project->gh_user;
        $repo   = $this->project->gh_project;

        try {
            $current = (string) ($gitHub->pulls->get($owner, $repo, (int) $finding->issue_number)->body ?? '');
        } catch (\Exception $exception) {
            throw $this->gitHubError($exception);
        }

        // Don't overwrite a description that changed on GitHub after the review, even if the tracker doesn't know it yet.
        if (AiReviewsTable::hashDescription($current) !== $finding->description_hash) {
            throw new \RuntimeException(
                'The description on GitHub changed since the review. Nothing was changed; the pull request will be reviewed again in the next review run.'
            );
        }

        $body = DescriptionSectionsHelper::replaceSections($current, $text);

        try {
            $response = $gitHub->pulls->edit($owner, $repo, (int) $finding->issue_number, null, $body);
        } catch (\Exception $exception) {
            throw $this->gitHubError($exception);
        }

        $stored = (string) ($response->body ?? $body);

        // Rendering is only for the tracker page; the webhook for the edit renders it again.
        try {
            $html = $gitHub->markdown->render($stored, 'gfm', $owner . '/' . $repo);
        } catch (\Exception $exception) {
            $html = null;
        }

        $this->findingModel->storeChangedDescription($finding, $stored, $html);
        $this->findingModel->process((int) $finding->id, AiReviewFindingsTable::STATE_ACCEPTED, $this->user->username, $text);

        $this->response->data    = (object) ['findingId' => (int) $finding->id, 'state' => AiReviewFindingsTable::STATE_ACCEPTED];
        $this->response->message = 'The testing instructions in the pull request description were replaced.';
    }
}
