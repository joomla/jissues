<?php

/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Controller\Hooks;

use App\Tracker\Controller\AbstractHookController;
use JTracker\Helper\GitHubHelper;

/**
 * Controller class receiving CI status updates from GitHub.
 *
 * Handles the "status" event (commit statuses, e.g. from external CI services) and the "check_run" event
 * (GitHub Actions and other GitHub Apps). The combined status of every open pull request whose head commit is the
 * reported commit is fetched from GitHub again and stored.
 *
 * @since  1.0
 */
class ReceiveStatusHook extends AbstractHookController
{
    /**
     * The type of the hook.
     *
     * @var    string
     * @since  1.0
     */
    protected $type = 'status';

    /**
     * The check run actions that change the status of a commit.
     *
     * "requested_action" and "rerequested" are followed by "created" or "completed" events of their own.
     *
     * @var    string[]
     * @since  1.0
     */
    private const CHECK_RUN_ACTIONS = ['created', 'completed'];

    /**
     * Prepare the response.
     *
     * @return  void
     *
     * @since   1.0
     */
    protected function prepareResponse()
    {
        $sha = $this->getCommitSha();

        if ($sha === null) {
            $this->response->message = 'No status change to process.';

            return;
        }

        try {
            $ids = $this->getOpenPullRequestIds($sha);

            if (!$ids) {
                $this->response->message = 'No open pull request has this head commit.';

                return;
            }

            $combinedStatus = (new GitHubHelper($this->github))->getCombinedStatus($this->project, $sha);

            $this->db->setQuery(
                $this->db->getQuery(true)
                    ->update($this->db->quoteName('#__issues'))
                    ->set($this->db->quoteName('merge_state') . ' = ' . $this->db->quote($combinedStatus->state))
                    ->set($this->db->quoteName('gh_merge_status') . ' = ' . $this->db->quote(json_encode($combinedStatus->statuses)))
                    ->where($this->db->quoteName('id') . ' IN (' . implode(',', $ids) . ')')
            )->execute();
        } catch (\Exception $e) {
            $logMessage = \sprintf(
                'Error updating the CI status of commit %s in %s/%s',
                $sha,
                $this->project->gh_user,
                $this->project->gh_project
            );

            $this->logger->error($logMessage, ['exception' => $e]);
            $this->setStatusCode(500);
            $this->response->error   = $logMessage . ': ' . $e->getMessage();
            $this->response->message = 'Hook data processed unsuccessfully.';

            return;
        }

        $this->response->message = \sprintf('Updated the CI status (%s) of %d item(s).', $combinedStatus->state, \count($ids));
    }

    /**
     * Get the commit SHA whose status changed.
     *
     * @return  string|null  The SHA, or null if the payload is not a status change.
     *
     * @since   1.0
     */
    private function getCommitSha(): ?string
    {
        if (!\is_object($this->hookData)) {
            return null;
        }

        // The check_run event
        if (isset($this->hookData->check_run)) {
            if (!\in_array($this->hookData->action ?? '', self::CHECK_RUN_ACTIONS, true)) {
                return null;
            }

            $sha = $this->hookData->check_run->head_sha ?? '';
        } else {
            // The status event
            $sha = $this->hookData->sha ?? '';
        }

        return \is_string($sha) && preg_match('/^[0-9a-f]{40}$/', $sha) ? $sha : null;
    }

    /**
     * Get the ids of the open pull requests in the project whose head commit is the given commit.
     *
     * @param   string  $sha  The commit SHA.
     *
     * @return  integer[]
     *
     * @since   1.0
     */
    private function getOpenPullRequestIds(string $sha): array
    {
        $ids = $this->db->setQuery(
            $this->db->getQuery(true)
                ->select($this->db->quoteName('id'))
                ->from($this->db->quoteName('#__issues'))
                ->where($this->db->quoteName('project_id') . ' = ' . (int) $this->project->project_id)
                ->where($this->db->quoteName('pr_head_sha') . ' = ' . $this->db->quote($sha))
                ->where($this->db->quoteName('has_code') . ' = 1')
                ->where($this->db->quoteName('closed_date') . ' IS NULL')
        )->loadColumn();

        return array_map('intval', $ids);
    }
}
