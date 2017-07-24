<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Controller\Hooks;

use App\Tracker\Controller\AbstractHookController;
use App\Tracker\Table\ReviewsTable;
use Joomla\Date\Date;

/**
 * Controller class receive and inject pull request review webhook events from GitHub.
 *
 * @since  1.0
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
	 * Prepare the response.
	 *
	 * @return  mixed
	 *
	 * @since   1.0
	 */
	protected function prepareResponse()
	{
		if (!isset($this->hookData->pull_request->number) || !is_object($this->hookData))
		{
			// If we can't get the issue number exit.
			$this->response->message = 'Hook data does not exist.';

			return;
		}

		// If the item is already in the database, update it; else, insert it.
		if (!$this->checkIssueExists((int) $this->hookData->pull_request->number))
		{
			$this->response->message = 'Pull Request does not exist in the system.';

			return;
		}

		try
		{
			$this->updateData();
		}
		catch (\Exception $e)
		{
			$logMessage = 'Uncaught Exception processing pull request webhook';

			$this->logger->critical($logMessage, ['exception' => $e]);
			$this->setStatusCode(500);
			$this->response->error = $logMessage . ': ' . $e->getMessage();
			$this->response->message = 'Hook data processed unsuccessfully.';
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
		try
		{
			$table = (new ReviewsTable($this->db))->load(
				[
					'issue_number' => $this->hookData->pull_request->number,
					'project_id' => $this->project->project_id,
				]
			);
		}
		catch (\Exception $e)
		{
			$logMessage = sprintf(
				'Error loading GitHub issue %s/%s #%d in the tracker',
				$this->project->gh_user,
				$this->project->gh_project,
				$this->hookData->pull_request->number
			);
			$this->setStatusCode(500);
			$this->response->error = $logMessage . ': ' . $e->getMessage();
			$this->logger->error($logMessage, ['exception' => $e]);

			return;
		}

		$action = $this->hookData->action;

		switch ($action)
		{
			case 'submitted':
				// Prepare the dates for insertion to the database
				$dateFormat = $this->db->getDateFormat();

				$data = [
					'issue_id'         => $this->hookData->pull_request->number,
					'project_id'       => $this->project->project_id,
					'review_id'        => $this->hookData->review->id,
					'reviewed_by'      => $this->hookData->review->user->login,
					'review_comment'   => $this->hookData->review->body,
					'review_submitted' => (new Date($this->hookData->issue->created_at))->format($dateFormat),
				];

				// It's impossible to submit a review in a dismissed state
				switch (strtoupper($this->hookData->review->state))
				{
					case 'APPROVE':
						$data['review_state'] = ReviewsTable::APPROVED_STATE;
						break;

					case 'REQUEST_CHANGES':
						$data['review_state'] = ReviewsTable::CHANGES_REQUIRED_STATE;
						break;

					default:
						$logMessage = sprintf(
							'Error parsing the review state for GitHub issue %s/%s #%d (review %d) in the tracker',
							$this->project->gh_user,
							$this->project->gh_project,
							$this->hookData->pull_request->number,
							$this->hookData->review->id
						);
						$this->setStatusCode(500);
						$this->response->error = $logMessage;
						$this->logger->error($logMessage);

						return;
				}

				try
				{
					$table->save($data);
				}
				catch (\Exception $e)
				{
					$logMessage = sprintf(
						'Error adding GitHub review %s/%s #%d (review #%d) in the tracker',
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

				try
				{
					$this->triggerEvent('onIssueAfterGithubReview', ['table' => $table, 'action' => $action]);
				}
				catch (\Exception $e)
				{
					$logMessage = sprintf(
						'Error processing `onIssueAfterGithubReview` event for issue number %d, review %d',
						$this->hookData->pull_request->number,
						$this->hookData->review->id
					);
					$this->setStatusCode(500);
					$this->response->error = $logMessage . ': ' . $e->getMessage();
					$this->logger->error($logMessage, ['exception' => $e]);

					return;
				}

				// Pull the user's avatar if it does not exist
				$this->pullUserAvatar($this->hookData->review->user->login);

				$this->response->message = 'Hook data processed successfully.';

				break;

			case 'edited':
				// Prepare the dates for insertion to the database
				$dateFormat = $this->db->getDateFormat();

				// TODO: Do we want to save in the activities table who changed the review?
				$data = [
					'review_comment'   => $this->hookData->changes->body,
					'review_submitted' => (new Date($this->hookData->issue->created_at))->format($dateFormat),
				];

				$table->load(
					array('review_id' => $this->hookData->review->id)
				);

				try
				{
					$table->save($data);
				}
				catch (\Exception $e)
				{
					$logMessage = sprintf(
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

				// TODO: Where is the dismissed comment stored in the hook data??
				$data = [
					'dismissed_by'      => $this->hookData->changes->body,
					'dismissed_on'      => (new Date($this->hookData->issue->created_at))->format($dateFormat),
					'review_state'      => ReviewsTable::DISMISSED_STATE
					// 'dismissed_comment' => $this->hookData->changes->body,
				];

				$table->load(
					array('review_id' => $this->hookData->review->id)
				);

				try
				{
					$table->save($data);
				}
				catch (\Exception $e)
				{
					$logMessage = sprintf(
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
}
