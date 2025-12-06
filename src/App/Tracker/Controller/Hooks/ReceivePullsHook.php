<?php

/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Controller\Hooks;

use App\Projects\TrackerProject;
use App\Tracker\Controller\AbstractHookController;
use App\Tracker\Model\IssueModel;
use App\Tracker\Table\IssuesTable;
use Joomla\Date\Date;
use JTracker\Github\GithubFactory;
use JTracker\Helper\GitHubHelper;

/**
 * Controller class receive and inject pull request webhook events from GitHub.
 *
 * @since  1.0
 * @note   Pull requests do not receive the `milestoned` or `unmilestoned` events.  Those are sent over the issues webhook, even for PRs.
 */
class ReceivePullsHook extends AbstractHookController
{
    /**
     * The type of hook being executed
     *
     * @var    string
     * @since  1.0
     */
    protected $type = 'pulls';

    /**
     * Data received from GitHub.
     *
     * @var    object
     * @since  1.0
     */
    protected $data;

    /**
     * Prepare the response.
     *
     * @return  mixed
     *
     * @since   1.0
     */
    protected function prepareResponse()
    {
        if (!isset($this->hookData->pull_request->number) || !\is_object($this->hookData)) {
            // If we can't get the issue number exit.
            $this->response->message = 'Hook data does not exist.';

            return;
        }

        // Stop running for label events
        if (\in_array($this->hookData->action, ['labeled', 'unlabeled'])) {
            $this->response->message = 'Ignoring label events now.';

            return;
        }

        // Pull or Issue ?
        $this->data = $this->hookData->pull_request;

        try {
            // If the item is already in the database, update it; else, insert it.
            if ($this->checkIssueExists((int) $this->data->number)) {
                $this->updateData();
            } else {
                $this->insertData();
            }
        } catch (\Exception $e) {
            $logMessage = 'Uncaught Exception processing pull request webhook';

            $this->logger->critical($logMessage, ['exception' => $e]);
            $this->setStatusCode(500);
            $this->response->error   = $logMessage . ': ' . $e->getMessage();
            $this->response->message = 'Hook data processed unsuccessfully.';
        }
    }

    /**
     * Method to insert data for an issue from GitHub
     *
     * @return  void
     *
     * @since   1.0
     */
    protected function insertData()
    {
        // Figure out the state based on the action
        $action = $this->hookData->action;

        switch ($action) {
            case 'opened':
            case 'closed':
            case 'reopened':
                $status = $this->processStatus($action);

                // Prepare the dates for insertion to the database
                $dateFormat = $this->db->getDateFormat();

                $gitHubHelper   = new GitHubHelper(GithubFactory::getInstance($this->getContainer()->get('app')));
                $combinedStatus = $gitHubHelper->getCombinedStatus($this->project, $this->data->head->sha);

                $data = [
                    'issue_number'    => $this->data->number,
                    'title'           => $this->data->title,
                    'description'     => $this->parseText($this->data->body),
                    'description_raw' => $this->data->body,
                    'status'          => ($status === null) ? 1 : $status,
                    'opened_date'     => (new Date($this->data->created_at))->format($dateFormat),
                    'opened_by'       => $this->data->user->login,
                    'modified_date'   => (new Date($this->data->updated_at))->format($dateFormat),
                    'modified_by'     => $this->hookData->sender->login,
                    'project_id'      => $this->project->project_id,
                    'has_code'        => 1,
                    'is_draft'        => $this->data->draft ? 1 : 0,
                    'build'           => $this->data->base->ref,
                    'pr_head_sha'     => $this->data->head->sha,
                    'pr_head_user'    => (isset($this->data->head->user)) ? $this->data->head->user->login : 'unknown_repository',
                    'pr_head_ref'     => $this->data->head->ref,
                    'commits'         => json_encode($gitHubHelper->getCommits($this->project, $this->data->number)),
                    'merge_state'     => $combinedStatus->state,
                    'gh_merge_status' => json_encode($combinedStatus->statuses),
                    'labels'          => $this->processLabels($this->data->number),
                ];

                // Add the closed date if the status is closed
                if ($this->data->closed_at) {
                    $data['closed_date'] = (new Date($this->data->closed_at))->format($dateFormat);
                    $data['closed_by']   = $this->hookData->sender->login;
                }

                // If the title has a [# in it, assume it's a JoomlaCode Tracker ID
                if (preg_match('/\[#([0-9]+)\]/', $this->data->title, $matches)) {
                    $data['foreign_number'] = $matches[1];
                } elseif (preg_match('/tracker_item_id=([0-9]+)/', $this->data->body, $matches)) {
                    // If the body has tracker_item_id= in it, that is a JoomlaCode Tracker ID
                    $data['foreign_number'] = $matches[1];
                }

                try {
                    $model = (new IssueModel($this->db))
                        ->setProject(new TrackerProject($this->db, $this->project))
                        ->add($data);
                } catch (\Exception $e) {
                    $logMessage = \sprintf(
                        'Error adding GitHub pull request %s/%s #%d to the tracker',
                        $this->project->gh_user,
                        $this->project->gh_project,
                        $this->data->number
                    );
                    $this->setStatusCode(500);
                    $this->response->error = $logMessage . ': ' . $e->getMessage();
                    $this->logger->error($logMessage, ['exception' => $e]);

                    return;
                }

                // Get a table object for the new record to process in the event listeners
                $table = (new IssuesTable($this->db))
                    ->load($model->getState()->get('issue_id'));

                try {
                    $this->triggerEvent('onPullAfterCreate', ['table' => $table, 'action' => $action]);
                } catch (\Exception $e) {
                    $logMessage = \sprintf(
                        'Error processing `onPullAfterCreate` event for issue number %d',
                        $this->data->number
                    );
                    $this->setStatusCode(500);
                    $this->response->error = $logMessage . ': ' . $e->getMessage();
                    $this->logger->error($logMessage, ['exception' => $e]);

                    return;
                }

                // Pull the user's avatar if it does not exist
                $this->pullUserAvatar($this->data->user->login);

                // Add a reopen record to the activity table if the action is reopened
                if ($action == 'reopened') {
                    try {
                        $this->addActivityEvent(
                            'reopen',
                            $data['modified_date'],
                            $this->hookData->sender->login,
                            $this->project->project_id,
                            $this->data->number
                        );
                    } catch (\RuntimeException $e) {
                        $logMessage = \sprintf(
                            'Error storing reopen activity to the database (Project ID: %1$d, Item #: %2$d)',
                            $this->project->project_id,
                            $this->data->number
                        );
                        $this->setStatusCode(500);
                        $this->response->error = $logMessage . ': ' . $e->getMessage();
                        $this->logger->error($logMessage, ['exception' => $e]);

                        return;
                    }
                }

                // Add a close record to the activity table if the status is closed
                if ($this->data->closed_at) {
                    try {
                        $this->addActivityEvent(
                            'close',
                            $data['closed_date'],
                            $this->hookData->sender->login,
                            $this->project->project_id,
                            $this->data->number
                        );
                    } catch (\RuntimeException $e) {
                        $logMessage = \sprintf(
                            'Error storing close activity to the database (Project ID: %1$d, Item #: %2$d)',
                            $this->project->project_id,
                            $this->data->number
                        );
                        $this->setStatusCode(500);
                        $this->response->error = $logMessage . ': ' . $e->getMessage();
                        $this->logger->error($logMessage, ['exception' => $e]);

                        return;
                    }
                }

                // Add a merge record to the activity table if the request was merged
                if ($action == 'closed' && $this->data->merged) {
                    try {
                        $this->addActivityEvent(
                            'merge',
                            $data['closed_date'],
                            $this->data->merged_by->login,
                            $this->project->project_id,
                            $this->data->number
                        );
                    } catch (\RuntimeException $e) {
                        $logMessage = \sprintf(
                            'Error storing merge activity to the database (Project ID: %1$d, Item #: %2$d)',
                            $this->project->project_id,
                            $this->data->number
                        );
                        $this->setStatusCode(500);
                        $this->response->error = $logMessage . ': ' . $e->getMessage();
                        $this->logger->error($logMessage, ['exception' => $e]);

                        return;
                    }
                }

                // Store was successful, update status
                $this->logger->info(
                    \sprintf(
                        'Added GitHub pull request %s/%s #%d (Database ID #%d) to the tracker.',
                        $this->project->gh_user,
                        $this->project->gh_project,
                        $this->data->number,
                        $table->id
                    )
                );

                $this->response->message = 'Hook data processed successfully.';

                break;

            default:
                $this->response->data = (object) [
                    'processed' => false,
                    'reason'    => 'Records can only be inserted for an open/close event.',
                ];

                $this->response->message = 'Hook data not processed.';

                break;
        }
    }

    /**
     * Method to update data for an issue from GitHub
     *
     * @return  void
     *
     * @since   1.0
     */
    protected function updateData()
    {
        try {
            $table = (new IssuesTable($this->db))->load(
                [
                    'issue_number' => $this->data->number,
                    'project_id'   => $this->project->project_id,
                ]
            );
        } catch (\Exception $e) {
            $logMessage = \sprintf(
                'Error loading GitHub issue %s/%s #%d in the tracker',
                $this->project->gh_user,
                $this->project->gh_project,
                $this->data->number
            );
            $this->setStatusCode(500);
            $this->response->error = $logMessage . ': ' . $e->getMessage();
            $this->logger->error($logMessage, ['exception' => $e]);

            return;
        }

        $action = $this->hookData->action;

        switch ($action) {
            case 'opened':
            case 'closed':
            case 'reopened':
            case 'synchronize':
                // Figure out the state based on the action
                $status = $this->processStatus($action, $table->status);

                // Prepare the dates for insertion to the database
                $dateFormat = $this->db->getDateFormat();

                // Plug in required fields based on the model and the current value of fields from the pull request data
                $data = [
                    'id'              => $table->id,
                    'title'           => $this->data->title,
                    'description'     => $this->parseText($this->data->body),
                    'description_raw' => $this->data->body,
                    'status'          => $status === null ? $table->status : $status,
                    'modified_date'   => (new Date($this->data->updated_at))->format($dateFormat),
                    'modified_by'     => $this->hookData->sender->login,
                    'priority'        => $table->priority,
                    'build'           => $table->build,
                    'rel_number'      => $table->rel_number,
                    'rel_type'        => $table->rel_type,
                    'milestone_id'    => $table->milestone_id,
                    'pr_head_sha'     => $this->data->head->sha,
                    'pr_head_user'    => (isset($this->data->head->user)) ? $this->data->head->user->login : 'unknown_repository',
                    'pr_head_ref'     => $this->data->head->ref,
                    'labels'          => $this->processLabels($this->data->number),
                ];

                // Add the closed date if the status is closed
                if ($this->data->closed_at) {
                    $data['closed_date'] = (new Date($this->data->closed_at))->format($dateFormat);
                }

                if (empty($data['build'])) {
                    $data['build'] = $this->hookData->repository->default_branch;
                }

                $model = (new IssueModel($this->db))
                    ->setProject(new TrackerProject($this->db, $this->project));

                // Check if the state has changed (e.g. open/closed)
                $oldState = $model->getOpenClosed($table->status);
                $state    = $status === null ? $oldState : $model->getOpenClosed($data['status']);

                $data['old_state'] = $oldState;
                $data['new_state'] = $state;

                $gitHubHelper = new GitHubHelper(GithubFactory::getInstance($this->getContainer()->get('app')));

                $commits = $gitHubHelper->getCommits($this->project, $this->data->number);

                $data['commits'] = json_encode($commits);

                $combinedStatus = $gitHubHelper->getCombinedStatus($this->project, $this->data->head->sha);

                $data['merge_state']     = $combinedStatus->state;
                $data['gh_merge_status'] = json_encode($combinedStatus->statuses);

                try {
                    $model->save($data);
                } catch (\Exception $e) {
                    $logMessage = \sprintf(
                        'Error updating GitHub pull request %s/%s #%d (Database ID #%d) to the tracker',
                        $this->project->gh_user,
                        $this->project->gh_project,
                        $this->data->number,
                        $table->id
                    );
                    $this->setStatusCode(500);
                    $this->response->error = $logMessage . ': ' . $e->getMessage();
                    $this->logger->error($logMessage, ['exception' => $e]);

                    return;
                }

                // Refresh the table object for the listeners
                $table->load($data['id']);

                try {
                    $this->triggerEvent('onPullAfterUpdate', ['table' => $table, 'action' => $action]);
                } catch (\Exception $e) {
                    $logMessage = \sprintf(
                        'Error processing `onPullAfterUpdate` event for issue number %d',
                        $this->data->number
                    );
                    $this->setStatusCode(500);
                    $this->response->error = $logMessage . ': ' . $e->getMessage();
                    $this->logger->error($logMessage, ['exception' => $e]);

                    return;
                }

                // Add a reopen record to the activity table if the status is reopened
                if ($action == 'reopened') {
                    try {
                        $this->addActivityEvent(
                            'reopen',
                            $this->data->updated_at,
                            $this->hookData->sender->login,
                            $this->project->project_id,
                            $this->data->number
                        );
                    } catch (\RuntimeException $e) {
                        $logMessage = \sprintf(
                            'Error storing reopen activity to the database (Project ID: %1$d, Item #: %2$d)',
                            $this->project->project_id,
                            $this->data->number
                        );
                        $this->setStatusCode(500);
                        $this->response->error = $logMessage . ': ' . $e->getMessage();
                        $this->logger->error($logMessage, ['exception' => $e]);

                        return;
                    }
                }

                // Add a synchronize record to the activity table if the action is synchronized
                if ($action == 'synchronize') {
                    try {
                        $this->addActivityEvent(
                            'synchronize',
                            $this->data->updated_at,
                            $this->hookData->sender->login,
                            $this->project->project_id,
                            $this->data->number
                        );
                    } catch (\RuntimeException $e) {
                        $logMessage = \sprintf(
                            'Error storing synchronize activity to the database (Project ID: %1$d, Item #: %2$d)',
                            $this->project->project_id,
                            $this->data->number
                        );
                        $this->setStatusCode(500);
                        $this->response->error = $logMessage . ': ' . $e->getMessage();
                        $this->logger->error($logMessage, ['exception' => $e]);

                        return;
                    }
                }

                // Add a close record to the activity table if the status is closed
                if ($this->data->closed_at) {
                    try {
                        $this->addActivityEvent(
                            'close',
                            $this->data->closed_at,
                            $this->hookData->sender->login,
                            $this->project->project_id,
                            $this->data->number
                        );
                    } catch (\RuntimeException $e) {
                        $logMessage = \sprintf(
                            'Error storing close activity to the database (Project ID: %1$d, Item #: %2$d)',
                            $this->project->project_id,
                            $this->data->number
                        );
                        $this->setStatusCode(500);
                        $this->response->error = $logMessage . ': ' . $e->getMessage();
                        $this->logger->error($logMessage, ['exception' => $e]);

                        return;
                    }
                }

                // Add a merge record to the activity table if the request was merged
                if ($action == 'closed' && $this->data->merged) {
                    try {
                        $this->addActivityEvent(
                            'merge',
                            $this->data->closed_at,
                            $this->data->merged_by->login,
                            $this->project->project_id,
                            $this->data->number
                        );
                    } catch (\RuntimeException $e) {
                        $logMessage = \sprintf(
                            'Error storing merge activity to the database (Project ID: %1$d, Item #: %2$d)',
                            $this->project->project_id,
                            $this->data->number
                        );
                        $this->setStatusCode(500);
                        $this->response->error = $logMessage . ': ' . $e->getMessage();
                        $this->logger->error($logMessage, ['exception' => $e]);

                        return;
                    }
                }

                // Store was successful, update status
                $this->logger->info(
                    \sprintf(
                        'Updated GitHub pull request %s/%s #%d (Database ID #%d) to the tracker.',
                        $this->project->gh_user,
                        $this->project->gh_project,
                        $this->data->number,
                        $table->id
                    )
                );

                $this->response->message = 'Hook data processed successfully.';

                break;

            case 'edited':
                // A false return will set an error message to the response
                if ($this->editPullRequest($table)) {
                    $this->response->message = 'Hook data processed successfully.';
                }

                break;

            case 'labeled':
            case 'unlabeled':
                $model = (new IssueModel($this->db))
                    ->setProject(new TrackerProject($this->db, $this->project));

                $state = $model->getOpenClosed($table->status);

                // Bind over the model's required data with the updated labels
                $data = [
                    'id'              => $table->id,
                    'title'           => $table->title,
                    'description'     => $table->description,
                    'description_raw' => $table->description_raw,
                    'modified_date'   => (new Date($this->data->updated_at))->format($this->db->getDateFormat()),
                    'modified_by'     => $this->hookData->sender->login,
                    'status'          => $table->status,
                    'priority'        => $table->priority,
                    'build'           => $table->build,
                    'rel_number'      => $table->rel_number,
                    'rel_type'        => $table->rel_type,
                    'milestone_id'    => $table->milestone_id,
                    'labels'          => $this->processLabels($table->issue_number),
                    'old_state'       => $state,
                    'new_state'       => $state,
                    'pr_head_sha'     => $table->pr_head_sha,
                    'pr_head_user'    => $table->pr_head_user,
                    'pr_head_ref'     => $table->pr_head_ref,
                ];

                try {
                    $model->save($data);
                } catch (\Exception $e) {
                    $logMessage = \sprintf(
                        'Error updating labels for GitHub pull request %s/%s #%d (Database ID #%d) in the tracker',
                        $this->project->gh_user,
                        $this->project->gh_project,
                        $this->hookData->issue->number,
                        $table->id
                    );
                    $this->setStatusCode(500);
                    $this->response->error = $logMessage . ': ' . $e->getMessage();
                    $this->logger->error($logMessage, ['exception' => $e]);

                    return;
                }

                // Refresh the table object for the listeners
                $table->load($data['id']);

                try {
                    $this->triggerEvent('onPullAfterUpdate', ['table' => $table, 'action' => $action]);
                } catch (\Exception $e) {
                    $logMessage = \sprintf(
                        'Error processing `onPullAfterUpdate` event for issue number %d',
                        $this->data->number
                    );
                    $this->setStatusCode(500);
                    $this->response->error = $logMessage . ': ' . $e->getMessage();
                    $this->logger->error($logMessage, ['exception' => $e]);

                    return;
                }

                // Add a labeled record to the activity table
                try {
                    $this->addActivityEvent(
                        $action,
                        $this->data->updated_at,
                        $this->hookData->sender->login,
                        $this->project->project_id,
                        $this->data->number
                    );
                } catch (\RuntimeException $e) {
                    $logMessage = \sprintf(
                        'Error storing labeled activity to the database (Project ID: %1$d, Item #: %2$d)',
                        $this->project->project_id,
                        $this->data->number
                    );
                    $this->setStatusCode(500);
                    $this->response->error = $logMessage . ': ' . $e->getMessage();
                    $this->logger->error($logMessage, ['exception' => $e]);

                    return;
                }

                // Store was successful, update status
                $this->logger->info(
                    \sprintf(
                        'Updated labels for GitHub pull request %s/%s #%d (Database ID #%d) in the tracker.',
                        $this->project->gh_user,
                        $this->project->gh_project,
                        $this->data->number,
                        $table->id
                    )
                );

                $this->response->message = 'Hook data processed successfully.';

                break;

            case 'ready_for_review':
                $model = (new IssueModel($this->db))
                    ->markIssueReadyForReview($table->id);

                $this->response->message = 'Hook data processed successfully.';

                break;

            default:
                $this->response->data = (object) [
                    'processed' => false,
                    'reason'    => "The '$action' action is not supported by this webhook.",
                ];

                $this->response->message = 'Hook data not processed.';

                break;
        }
    }

    /**
     * Process a pull request for a `edited` webhook event
     *
     * @param   IssuesTable  $table  The table object for the pull request being edited
     *
     * @return  boolean
     *
     * @since   1.0
     */
    private function editPullRequest(IssuesTable $table)
    {
        // Pull requests will only track changes on the title and body fields for now
        $data = [];

        if (isset($this->hookData->changes->title)) {
            $data['title'] = $this->data->title;
        }

        if (isset($this->hookData->changes->body)) {
            $data['description']     = $this->parseText($this->data->body);
            $data['description_raw'] = $this->data->body;
        }

        // Ensure the data array isn't empty for some reason; if it is there's nothing to do here
        if (empty($data)) {
            return true;
        }

        $model = (new IssueModel($this->db))
            ->setProject(new TrackerProject($this->db, $this->project));

        $state = $model->getOpenClosed($table->status);

        // Bind over the rest of the model's required data
        $data = array_merge(
            $data,
            [
                'id'              => $table->id,
                'title'           => $data['title'] ?? $table->title,
                'description'     => $data['description'] ?? $table->description,
                'description_raw' => $data['description_raw'] ?? $table->description_raw,
                'modified_date'   => (new Date($this->data->updated_at))->format($this->db->getDateFormat()),
                'modified_by'     => $this->hookData->sender->login,
                'status'          => $table->status,
                'priority'        => $table->priority,
                'build'           => $table->build,
                'rel_number'      => $table->rel_number,
                'rel_type'        => $table->rel_type,
                'milestone_id'    => $table->milestone_id,
                'labels'          => $table->labels,
                'old_state'       => $state,
                'new_state'       => $state,
                'pr_head_sha'     => $table->pr_head_sha,
                'pr_head_user'    => $table->pr_head_user,
                'pr_head_ref'     => $table->pr_head_ref,
            ]
        );

        try {
            $model->save($data);
        } catch (\Exception $e) {
            $logMessage = \sprintf(
                'Error editing GitHub pull request %s/%s #%d (Database ID #%d) in the tracker',
                $this->project->gh_user,
                $this->project->gh_project,
                $this->data->number,
                $table->id
            );
            $this->setStatusCode(500);
            $this->response->error = $logMessage . ': ' . $e->getMessage();
            $this->logger->error($logMessage, ['exception' => $e]);

            return false;
        }

        // Refresh the table object for the listeners
        $table->load($data['id']);

        try {
            $this->triggerEvent('onPullAfterUpdate', ['table' => $table, 'action' => 'edited']);
        } catch (\Exception $e) {
            $logMessage = \sprintf(
                'Error processing `onPullAfterUpdate` event for issue number %d',
                $this->data->number
            );
            $this->setStatusCode(500);
            $this->response->error = $logMessage . ': ' . $e->getMessage();
            $this->logger->error($logMessage, ['exception' => $e]);

            return false;
        }

        // Add an edit record to the activity table
        try {
            $this->addActivityEvent(
                'edited',
                $this->data->updated_at,
                $this->hookData->sender->login,
                $this->project->project_id,
                $this->data->number
            );
        } catch (\RuntimeException $e) {
            $logMessage = \sprintf(
                'Error storing edit activity to the database (Project ID: %1$d, Item #: %2$d)',
                $this->project->project_id,
                $this->data->number
            );
            $this->setStatusCode(500);
            $this->response->error = $logMessage . ': ' . $e->getMessage();
            $this->logger->error($logMessage, ['exception' => $e]);

            return false;
        }

        // Store was successful, update status
        $this->logger->info(
            \sprintf(
                'Edited GitHub pull request %s/%s #%d (Database ID #%d) in the tracker.',
                $this->project->gh_user,
                $this->project->gh_project,
                $this->data->number,
                $table->id
            )
        );

        return true;
    }
}
