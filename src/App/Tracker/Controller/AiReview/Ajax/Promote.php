<?php

/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2026 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Controller\AiReview\Ajax;

use App\Tracker\Table\AiReviewFindingsTable;

/**
 * Promote a pull request for testing by setting the configured promotion label on GitHub. Site admins only.
 *
 * The label is set with the admin's own GitHub token. The post in the testing channel is made when GitHub reports the
 * label change through the webhook, the same way as when the label is set directly on GitHub.
 *
 * @since  1.0
 */
class Promote extends AbstractFindingController
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
        $finding = $this->loadFinding([AiReviewFindingsTable::KIND_TEST_SUITABILITY]);

        if (!$finding->has_code) {
            throw new \UnexpectedValueException('Only pull requests can be promoted for testing.');
        }

        $label = (string) $this->getContainer()->get('app')->get('mattermost.promote_label', '');

        if ($label === '') {
            throw new \RuntimeException('No promotion label is configured (mattermost.promote_label).');
        }

        $gitHub = $this->getUserGitHub();

        try {
            $gitHub->issues->labels->add($this->project->gh_user, $this->project->gh_project, (int) $finding->issue_number, [$label]);
        } catch (\Exception $exception) {
            throw $this->gitHubError($exception);
        }

        $this->findingModel->process((int) $finding->id, AiReviewFindingsTable::STATE_ACCEPTED, $this->user->username, $label);

        $this->response->data    = (object) ['findingId' => (int) $finding->id, 'state' => AiReviewFindingsTable::STATE_ACCEPTED];
        $this->response->message = \sprintf(
            'The label "%s" was set. The pull request is posted to the testing channel as soon as GitHub reports the label.',
            $label
        );
    }
}
