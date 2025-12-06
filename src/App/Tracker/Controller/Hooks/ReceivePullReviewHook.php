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
use App\Tracker\Table\ReviewsTable;
use Joomla\Date\Date;

/**
 * Controller class receive and inject pull request review webhook events from GitHub.
 *
 * @since  1.0
 * @TODO:  In some cases the comment is sent by GitHub first as a edited event immediately followed
 *         by a submitted event (leading the latter to have a 500 back to GitHub). This also gives
 *         Undefined property: stdClass::$body on line 284 of this file
 */
class ReceivePullReviewHook extends AbstractHookController
{
    /**
     * The type of hook being executed
     *
     * @var    string
     * @since  1.0
     */
    protected $type = 'pullReview';

    /**
     * Checks if an issue exists
     *
     * @param   integer  $reviewId  Pull Request Review ID to check
     *
     * @return  string|null  The issue ID if it exists or null
     *
     * @since   1.0
     */
    protected function checkReviewExists($reviewId)
    {
        try {
            return $this->db->setQuery(
                $this->db->getQuery(true)
                    ->select($this->db->quoteName('review_id'))
                    ->from($this->db->quoteName('#__issue_reviews'))
                    ->where($this->db->quoteName('project_id') . ' = ' . (int) $this->project->project_id)
                    ->where($this->db->quoteName('review_id') . ' = ' . $reviewId)
            )->loadResult();
        } catch (\RuntimeException $e) {
            $this->logger->error('Error checking the database for the GitHub ID', ['exception' => $e]);
            $this->getContainer()->get('app')->close();
        }
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
        if (!isset($this->hookData->pull_request->number) || !\is_object($this->hookData)) {
            // If we can't get the issue number exit.
            $this->response->message = 'Hook data does not exist.';

            return;
        }

        // If the item is already in the database, update it; else, insert it.
        if (!$this->checkIssueExists((int) $this->hookData->pull_request->number)) {
            $this->logger->warning(
                \sprintf(
                    'GitHub issue %s/%s #%d is missing from the tracker - creating it from the pull request review hook.',
                    $this->project->gh_user,
                    $this->project->gh_project,
                    $this->hookData->pull_request->number
                )
            );

            if (!$this->insertIssue()) {
                return;
            }
        }

        try {
            if ($this->hookData->action === 'submitted') {
                $this->insertData();
            } else {
                $this->updateData();
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
     * Method to update data for an issue from GitHub
     *
     * @return  boolean
     *
     * @since   1.0
     */
    protected function insertData()
    {
        $table = new ReviewsTable($this->db);

        // Prepare the dates for insertion to the database
        $dateFormat = $this->db->getDateFormat();

        $data = [
            'issue_id'         => $this->hookData->pull_request->number,
            'project_id'       => $this->project->project_id,
            'review_id'        => $this->hookData->review->id,
            'reviewed_by'      => $this->hookData->review->user->login,
            'review_comment'   => $this->hookData->review->body,
            'review_submitted' => (new Date($this->hookData->pull_request->created_at))->format($dateFormat),
            'commit_id'        => $this->hookData->review->commit_id,
        ];

        // It's impossible to submit a review in a dismissed state
        switch (strtoupper($this->hookData->review->state)) {
            case 'APPROVED':
                $data['review_state'] = ReviewsTable::APPROVED_STATE;

                break;

            case 'CHANGES_REQUESTED':
                $data['review_state'] = ReviewsTable::CHANGES_REQUIRED_STATE;

                break;

            case 'COMMENTED':
                $data['review_state'] = ReviewsTable::COMMENTED;

                break;

            default:
                $logMessage = \sprintf(
                    'Error parsing the review state for GitHub issue %s/%s #%d (review %d) in the tracker',
                    $this->project->gh_user,
                    $this->project->gh_project,
                    $this->hookData->pull_request->number,
                    $this->hookData->review->id
                );
                $this->setStatusCode(500);
                $this->response->error = $logMessage;
                $this->logger->error($logMessage);

                return false;
        }

        try {
            $table->save($data);
        } catch (\Exception $e) {
            $logMessage = \sprintf(
                'Error adding GitHub review %s/%s #%d (review #%d) in the tracker',
                $this->project->gh_user,
                $this->project->gh_project,
                $this->hookData->pull_request->number,
                $this->hookData->review->id
            );
            $this->setStatusCode(500);
            $this->response->error = $logMessage . ': ' . $e->getMessage();
            $this->logger->error($logMessage, ['exception' => $e]);

            return false;
        }

        try {
            $this->triggerEvent('onIssueAfterGithubReview', ['table' => $table, 'action' => $this->hookData->action]);
        } catch (\Exception $e) {
            $logMessage = \sprintf(
                'Error processing `onIssueAfterGithubReview` event for issue number %d, review %d',
                $this->hookData->pull_request->number,
                $this->hookData->review->id
            );
            $this->setStatusCode(500);
            $this->response->error = $logMessage . ': ' . $e->getMessage();
            $this->logger->error($logMessage, ['exception' => $e]);

            return false;
        }

        // Pull the user's avatar if it does not exist
        $this->pullUserAvatar($this->hookData->review->user->login);

        $this->response->message = 'Hook data processed successfully.';

        return true;
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
        if (!$this->checkReviewExists($this->hookData->review->id)) {
            $logMessage = \sprintf(
                'Error finding GitHub review %s/%s #%d (review %d) in the tracker. Creating it.',
                $this->project->gh_user,
                $this->project->gh_project,
                $this->hookData->pull_request->number,
                $this->hookData->review->id
            );
            $this->logger->warning($logMessage);

            if (!$this->insertData()) {
                return;
            }
        }

        try {
            $table = (new ReviewsTable($this->db))->load(
                [
                    'review_id' => $this->hookData->review->id,
                ]
            );
        } catch (\Exception $e) {
            $logMessage = \sprintf(
                'Error loading GitHub review %s/%s #%d (review %d) in the tracker',
                $this->project->gh_user,
                $this->project->gh_project,
                $this->hookData->pull_request->number,
                $this->hookData->review->id
            );
            $this->setStatusCode(500);
            $this->response->error = $logMessage . ': ' . $e->getMessage();
            $this->logger->error($logMessage, ['exception' => $e]);

            return;
        }

        $action = $this->hookData->action;

        switch ($action) {
            case 'edited':
                // Prepare the dates for insertion to the database
                $dateFormat = $this->db->getDateFormat();

                // TODO: Do we want to save in the activities table who changed the review?
                $data = [
                    'review_comment'   => $this->hookData->changes->body,
                    'review_submitted' => (new Date($this->hookData->pull_request->created_at))->format($dateFormat),
                ];

                $table->load(
                    ['review_id' => $this->hookData->review->id]
                );

                try {
                    $table->save($data);
                } catch (\Exception $e) {
                    $logMessage = \sprintf(
                        'Error updating GitHub review %s/%s #%d (review #%d) in the tracker',
                        $this->project->gh_user,
                        $this->project->gh_project,
                        $this->hookData->pull_request->number,
                        $this->hookData->review->id
                    );
                    $this->setStatusCode(500);
                    $this->response->error = $logMessage . ': ' . $e->getMessage();
                    $this->logger->error($logMessage, ['exception' => $e]);

                    return;
                }

                $this->response->message = 'Hook data processed successfully.';

                break;

            case 'dismissed':
                // Prepare the dates for insertion to the database
                $dateFormat = $this->db->getDateFormat();

                /**
                 * TODO: GitHub doesn't submit the person who authored the dismissal OR the comment associated with
                 *       the dismissal in the webhook. We'll have to try and access it another way. The intention is
                 *       to store these in the "dismissed_comment" and "dismissed_by" fields in the DB.
                 */
                $data = [
                    'dismissed_on' => (new Date($this->hookData->pull_request->created_at))->format($dateFormat),
                    'review_state' => ReviewsTable::DISMISSED_STATE,
                ];

                $table->load(
                    ['review_id' => $this->hookData->review->id]
                );

                try {
                    $table->save($data);
                } catch (\Exception $e) {
                    $logMessage = \sprintf(
                        'Error updating GitHub review %s/%s #%d (review #%d) in the tracker',
                        $this->project->gh_user,
                        $this->project->gh_project,
                        $this->hookData->pull_request->number,
                        $this->hookData->review->id
                    );
                    $this->setStatusCode(500);
                    $this->response->error = $logMessage . ': ' . $e->getMessage();
                    $this->logger->error($logMessage, ['exception' => $e]);

                    return;
                }

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
     * Method to insert data for an issue from GitHub
     *
     * @return  boolean
     *
     * @since   1.0
     */
    protected function insertIssue()
    {
        // Prepare the dates for insertion to the database
        $dateFormat = $this->db->getDateFormat();

        $data                    = [];
        $data['issue_number']    = $this->hookData->pull_request->number;
        $data['title']           = $this->hookData->pull_request->title;
        $data['description']     = $this->parseText($this->hookData->pull_request->body);
        $data['description_raw'] = $this->hookData->pull_request->body;
        $data['status']          = ($this->hookData->pull_request->state) == 'open' ? 1 : 10;
        $data['opened_date']     = (new Date($this->hookData->pull_request->created_at))->format($dateFormat);
        $data['opened_by']       = $this->hookData->pull_request->user->login;
        $data['modified_date']   = (new Date($this->hookData->pull_request->updated_at))->format($dateFormat);
        $data['modified_by']     = $this->hookData->sender->login;
        $data['project_id']      = $this->project->project_id;
        $data['build']           = $this->hookData->repository->default_branch;
        $data['pr_head_user']    = (isset($this->hookData->pull_request->head->user))
            ? $this->hookData->pull_request->head->user->login
            : 'unknown_repository';
        $data['pr_head_ref'] = $this->hookData->pull_request->head->ref;
        $data['pr_head_sha'] = $this->hookData->pull_request->head->sha;

        // Add the closed date if the status is closed
        if ($this->hookData->pull_request->closed_at) {
            $data['closed_date'] = (new Date($this->hookData->pull_request->closed_at))->format($dateFormat);
            $data['closed_by']   = $this->hookData->sender->login;
        }

        // If the title has a [# in it, assume it's a JoomlaCode Tracker ID
        if (preg_match('/\[#([0-9]+)\]/', $this->hookData->pull_request->title, $matches)) {
            $data['foreign_number'] = $matches[1];
        } elseif (preg_match('/tracker_item_id=([0-9]+)/', $this->hookData->pull_request->body, $matches)) {
            // If the body has tracker_item_id= in it, that is a JoomlaCode Tracker ID
            $data['foreign_number'] = $matches[1];
        }

        // Process labels for the item
        $data['labels'] = $this->processLabels($this->hookData->pull_request->number);

        try {
            $model = (new IssueModel($this->db))
                ->setProject(new TrackerProject($this->db, $this->project))
                ->add($data);
        } catch (\Exception $e) {
            $logMessage = \sprintf(
                'Error adding GitHub issue %s/%s #%d to the tracker',
                $this->project->gh_user,
                $this->project->gh_project,
                $this->hookData->pull_request->number
            );
            $this->setStatusCode(500);
            $this->response->error = $logMessage . ': ' . $e->getMessage();
            $this->logger->error($logMessage, ['exception' => $e]);

            return false;
        }

        // Get a table object for the new record to process in the event listeners
        $table = (new IssuesTable($this->db))
            ->load($model->getState()->get('issue_id'));

        try {
            $this->triggerEvent('onCommentAfterCreateIssue', ['table' => $table]);
        } catch (\Exception $e) {
            $logMessage = \sprintf(
                'Error processing `onCommentAfterCreateIssue` event for issue number %d',
                $this->hookData->pull_request->number
            );
            $this->setStatusCode(500);
            $this->response->error = $logMessage . ': ' . $e->getMessage();
            $this->logger->error($logMessage, ['exception' => $e]);

            return false;
        }

        // Pull the user's avatar if it does not exist
        $this->pullUserAvatar($this->hookData->pull_request->user->login);

        // Add a close record to the activity table if the status is closed
        if ($this->hookData->pull_request->closed_at) {
            try {
                $this->addActivityEvent(
                    'close',
                    $data['closed_date'],
                    $this->hookData->sender->login,
                    $this->project->project_id,
                    $this->hookData->pull_request->number
                );
            } catch (\RuntimeException $e) {
                $logMessage = \sprintf(
                    'Error storing close activity to the database (Project ID: %1$d, Item #: %2$d)',
                    $this->project->project_id,
                    $this->hookData->pull_request->number
                );
                $this->setStatusCode(500);
                $this->response->error = $logMessage . ': ' . $e->getMessage();
                $this->logger->error($logMessage, ['exception' => $e]);

                return false;
            }
        }

        // Store was successful, update status
        $this->logger->info(
            \sprintf(
                'Added GitHub issue %s/%s #%d (Database ID #%d) to the tracker.',
                $this->project->gh_user,
                $this->project->gh_project,
                $this->hookData->pull_request->number,
                $table->id
            )
        );

        return true;
    }
}
