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
 * Dismiss an AI review finding or mark it as done. Site admins only.
 *
 * Nothing is changed on GitHub. "Done" means the admin handled the finding by hand.
 *
 * @since  1.0
 */
class Process extends AbstractFindingController
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
        $state = $this->getContainer()->get('app')->getInput()->getCmd('state');

        if (!\in_array($state, [AiReviewFindingsTable::STATE_DISMISSED, AiReviewFindingsTable::STATE_DONE], true)) {
            throw new \UnexpectedValueException('Unexpected finding state.');
        }

        $finding = $this->loadFinding();

        $this->findingModel->process((int) $finding->id, $state, $this->user->username);

        $this->response->data    = (object) ['findingId' => (int) $finding->id, 'state' => $state];
        $this->response->message = $state === AiReviewFindingsTable::STATE_DONE ? 'Marked as done.' : 'Dismissed.';
    }
}
