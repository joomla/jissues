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

/**
 * Controller class receive and inject issue webhook events from GitHub.
 *
 * @since  1.0
 */
class ReceiveIssuesHook extends AbstractHookController
{
    /**
     * The type of hook being executed
     *
     * @var    string
     * @since  1.0
     */
    protected $type = 'issues';

    /**
     * Prepare the response.
     *
     * @return  mixed
     *
     * @since   1.0
     */
    protected function prepareResponse()
    {
        if (!isset($this->hookData->issue->number) || !\is_object($this->hookData)) {
            // If we can't get the issue number exit.
            $this->response->message = 'Hook data does not exist';

            return;
        }

        try {
            // If the item is already in the database, update it; else, insert it.
            if ($this->checkIssueExists((int) $this->hookData->issue->number)) {
                $this->updateData();
            } else {
                $this->insertData();
            }
        } catch (\Exception $e) {
            $logMessage = 'Uncaught Exception processing issue webhook';

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

                $data = [
                    'issue_number'    => $this->hookData->issue->number,
                    'title'           => $this->hookData->issue->title,
                    'description'     => $this->parseText($this->hookData->issue->body),
                    'description_raw' => $this->hookData->issue->body,
                    'status'          => ($status === null) ? 1 : $status,
                    'opened_date'     => (new \DateTime($this->hookData->issue->created_at))->format($dateFormat),
                    'opened_by'       => $this->hookData->issue->user->login,
                    'modified_date'   => (new \DateTime($this->hookData->issue->updated_at))->format($dateFormat),
                    'modified_by'     => $this->hookData->sender->login,
                    'project_id'      => $this->project->project_id,
                    'build'           => $this->hookData->repository->default_branch,
                    'labels'          => $this->processLabels($this->hookData->issue->number),
                ];

                // Add the closed date if the status is closed
                if ($this->hookData->issue->closed_at) {
                    $data['closed_date'] = (new \DateTime($this->hookData->issue->closed_at))->format($dateFormat);
                    $data['closed_by']   = $this->hookData->sender->login;
                }

                // If the title has a [# in it, assume it's a JoomlaCode Tracker ID
                if (preg_match('/\[#([0-9]+)\]/', $this->hookData->issue->title, $matches)) {
                    $data['foreign_number'] = $matches[1];
                } elseif (preg_match('/tracker_item_id=([0-9]+)/', $this->hookData->issue->body, $matches)) {
                    // If the body has tracker_item_id= in it, that is a JoomlaCode Tracker ID
                    $data['foreign_number'] = $matches[1];
                }

                try {
                    $model = (new IssueModel($this->db))
                        ->setProject(new TrackerProject($this->db, $this->project))
                        ->add($data);
                } catch (\Exception $e) {
                    $logMessage = \sprintf(
                        'Error adding GitHub issue %s/%s #%d to the tracker',
                        $this->project->gh_user,
                        $this->project->gh_project,
                        $this->hookData->issue->number
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
                    $this->triggerEvent('onIssueAfterCreate', ['table' => $table, 'action' => $action]);
                } catch (\Exception $e) {
                    $logMessage = \sprintf(
                        'Error processing `onIssueAfterCreate` event for issue number %d',
                        $this->hookData->issue->number
                    );
                    $this->setStatusCode(500);
                    $this->response->error = $logMessage . ': ' . $e->getMessage();
                    $this->logger->error($logMessage, ['exception' => $e]);

                    return;
                }

                // Pull the user's avatar if it does not exist
                $this->pullUserAvatar($this->hookData->issue->user->login);

                // Add a reopen record to the activity table if the status is closed
                if ($action == 'reopened') {
                    try {
                        $this->addActivityEvent(
                            'reopen',
                            $data['modified_date'],
                            $this->hookData->sender->login,
                            $this->project->project_id,
                            $this->hookData->issue->number
                        );
                    } catch (\RuntimeException $e) {
                        $logMessage = \sprintf(
                            'Error storing reopen activity to the database (Project ID: %1$d, Item #: %2$d)',
                            $this->project->project_id,
                            $this->hookData->issue->number
                        );
                        $this->setStatusCode(500);
                        $this->response->error = $logMessage . ': ' . $e->getMessage();
                        $this->logger->error($logMessage, ['exception' => $e]);

                        return;
                    }
                }

                // Add a close record to the activity table if the status is closed
                if ($this->hookData->issue->closed_at) {
                    try {
                        $this->addActivityEvent(
                            'close',
                            $data['closed_date'],
                            $this->hookData->issue->user->login,
                            $this->project->project_id,
                            $this->hookData->issue->number
                        );
                    } catch (\RuntimeException $e) {
                        $logMessage = \sprintf(
                            'Error storing close activity to the database (Project ID: %1$d, Item #: %2$d)',
                            $this->project->project_id,
                            $this->hookData->issue->number
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
                        'Added GitHub issue %s/%s #%d (Database ID #%d) to the tracker.',
                        $this->project->gh_user,
                        $this->project->gh_project,
                        $this->hookData->issue->number,
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
        $table = new IssuesTable($this->db);

        try {
            $table->load(
                [
                    'issue_number' => $this->hookData->issue->number,
                    'project_id'   => $this->project->project_id,
                ]
            );
        } catch (\Exception $e) {
            $logMessage = \sprintf(
                'Error loading GitHub issue %s/%s #%d in the tracker',
                $this->project->gh_user,
                $this->project->gh_project,
                $this->hookData->issue->number
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
                // Figure out the state based on the action
                $status = $this->processStatus($action, $table->status);

                // Prepare the dates for insertion to the database
                $dateFormat = $this->db->getDateFormat();

                // Plug in required fields based on the model and the current value of fields from the pull request data
                $data = [
                    'id'              => $table->id,
                    'title'           => $this->hookData->issue->title,
                    'description'     => $this->parseText($this->hookData->issue->body),
                    'description_raw' => $this->hookData->issue->body,
                    'status'          => $status === null ? $table->status : $status,
                    'modified_date'   => (new \DateTime($this->hookData->issue->updated_at))->format($dateFormat),
                    'modified_by'     => $this->hookData->sender->login,
                    'priority'        => $table->priority,
                    'build'           => $table->build,
                    'rel_number'      => $table->rel_number,
                    'rel_type'        => $table->rel_type,
                    'milestone_id'    => $table->milestone_id,
                    'labels'          => $this->processLabels($this->hookData->issue->number),
                ];

                // Add the closed date if the status is closed
                if ($this->hookData->issue->closed_at) {
                    $data['closed_date'] = (new \DateTime($this->hookData->issue->closed_at))->format($dateFormat);
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

                try {
                    $model->save($data);
                } catch (\Exception $e) {
                    $logMessage = \sprintf(
                        'Error updating GitHub issue %s/%s #%d (Database ID #%d) in the tracker',
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
                    $this->triggerEvent('onIssueAfterUpdate', ['table' => $table, 'action' => $action]);
                } catch (\Exception $e) {
                    $logMessage = \sprintf(
                        'Error processing `onIssueAfterUpdate` event for issue number %d',
                        $this->hookData->issue->number
                    );
                    $this->setStatusCode(500);
                    $this->response->error = $logMessage . ': ' . $e->getMessage();
                    $this->logger->error($logMessage, ['exception' => $e]);

                    return;
                }

                // Add a reopen record to the activity table if the status is closed
                if ($action == 'reopened') {
                    try {
                        $this->addActivityEvent(
                            'reopen',
                            $this->hookData->issue->updated_at,
                            $this->hookData->sender->login,
                            $this->project->project_id,
                            $this->hookData->issue->number
                        );
                    } catch (\RuntimeException $e) {
                        $logMessage = \sprintf(
                            'Error storing reopen activity to the database (Project ID: %1$d, Item #: %2$d)',
                            $this->project->project_id,
                            $this->hookData->issue->number
                        );
                        $this->setStatusCode(500);
                        $this->response->error = $logMessage . ': ' . $e->getMessage();
                        $this->logger->error($logMessage, ['exception' => $e]);

                        return;
                    }
                }

                // Add a close record to the activity table if the status is closed
                if ($this->hookData->issue->closed_at) {
                    try {
                        $this->addActivityEvent(
                            'close',
                            $this->hookData->issue->closed_at,
                            $this->hookData->sender->login,
                            $this->project->project_id,
                            $this->hookData->issue->number
                        );
                    } catch (\RuntimeException $e) {
                        $logMessage = \sprintf(
                            'Error storing close activity to the database (Project ID: %1$d, Item #: %2$d)',
                            $this->project->project_id,
                            $this->hookData->issue->number
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
                        'Updated GitHub issue %s/%s #%d (Database ID #%d) to the tracker.',
                        $this->project->gh_user,
                        $this->project->gh_project,
                        $this->hookData->issue->number,
                        $table->id
                    )
                );

                $this->response->message = 'Hook data processed successfully.';

                break;

            case 'edited':
                // A false return will set an error message to the response
                if ($this->editIssue($table)) {
                    $this->response->message = 'Hook data processed successfully.';
                }

                break;

            case 'labeled':
            case 'unlabeled':
                $model = (new IssueModel($this->db))
                    ->setProject(new TrackerProject($this->db, $this->project));

                $state = $model->getOpenClosed($table->status);

                // Plug in required fields based on the model and the current value of fields from the pull request data
                $data = [
                    'id'              => $table->id,
                    'title'           => $table->title,
                    'description'     => $table->description,
                    'description_raw' => $table->description_raw,
                    'modified_date'   => (new \DateTime($this->hookData->issue->updated_at))->format($this->db->getDateFormat()),
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
                ];

                try {
                    $model->save($data);
                } catch (\Exception $e) {
                    $logMessage = \sprintf(
                        'Error updating labels for GitHub issue %s/%s #%d (Database ID #%d) in the tracker',
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
                    $this->triggerEvent('onIssueAfterUpdate', ['table' => $table, 'action' => $action]);
                } catch (\Exception $e) {
                    $logMessage = \sprintf(
                        'Error processing `onIssueAfterUpdate` event for issue number %d',
                        $this->hookData->issue->number
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
                        $this->hookData->issue->updated_at,
                        $this->hookData->sender->login,
                        $this->project->project_id,
                        $this->hookData->issue->number
                    );
                } catch (\RuntimeException $e) {
                    $logMessage = \sprintf(
                        'Error storing label activity to the database (Project ID: %1$d, Item #: %2$d)',
                        $this->project->project_id,
                        $this->hookData->issue->number
                    );
                    $this->setStatusCode(500);
                    $this->response->error = $logMessage . ': ' . $e->getMessage();
                    $this->logger->error($logMessage, ['exception' => $e]);

                    return;
                }

                // Store was successful, update status
                $this->logger->info(
                    \sprintf(
                        'Updated labels for GitHub issue %s/%s #%d (Database ID #%d) in the tracker.',
                        $this->project->gh_user,
                        $this->project->gh_project,
                        $this->hookData->issue->number,
                        $table->id
                    )
                );

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
     * Process an issue for a `edited` webhook event
     *
     * @param   IssuesTable  $table  The table object for the issue being edited
     *
     * @return  boolean
     *
     * @since   1.0
     */
    private function editIssue(IssuesTable $table)
    {
        // Issues will only track changes on the title and body fields for now
        $data = [];

        if (isset($this->hookData->changes->title)) {
            $data['title'] = $this->hookData->issue->title;
        }

        if (isset($this->hookData->changes->body)) {
            $data['description']     = $this->parseText($this->hookData->issue->body);
            $data['description_raw'] = $this->hookData->issue->body;
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
                'modified_date'   => (new \DateTime($this->hookData->issue->updated_at))->format($this->db->getDateFormat()),
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
            ]
        );

        try {
            $model->save($data);
        } catch (\Exception $e) {
            $logMessage = \sprintf(
                'Error editing GitHub issue %s/%s #%d (Database ID #%d) in the tracker',
                $this->project->gh_user,
                $this->project->gh_project,
                $this->hookData->issue->number,
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
            $this->triggerEvent('onIssueAfterUpdate', ['table' => $table, 'action' => 'edited']);
        } catch (\Exception $e) {
            $logMessage = \sprintf(
                'Error processing `onIssueAfterUpdate` event for issue number %d',
                $this->hookData->issue->number
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
                $this->hookData->issue->updated_at,
                $this->hookData->sender->login,
                $this->project->project_id,
                $this->hookData->issue->number
            );
        } catch (\RuntimeException $e) {
            $logMessage = \sprintf(
                'Error storing edited activity to the database (Project ID: %1$d, Item #: %2$d)',
                $this->project->project_id,
                $this->hookData->issue->number
            );
            $this->setStatusCode(500);
            $this->response->error = $logMessage . ': ' . $e->getMessage();
            $this->logger->error($logMessage, ['exception' => $e]);

            return false;
        }

        // Store was successful, update status
        $this->logger->info(
            \sprintf(
                'Edited GitHub issue %s/%s #%d (Database ID #%d) in the tracker.',
                $this->project->gh_user,
                $this->project->gh_project,
                $this->hookData->issue->number,
                $table->id
            )
        );

        return true;
    }
}
