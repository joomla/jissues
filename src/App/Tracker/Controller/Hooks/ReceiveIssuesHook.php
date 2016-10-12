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

/**
 * Controller class receive and inject issue reports from GitHub
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
		if (!isset($this->hookData->issue->number) || !is_object($this->hookData))
		{
			// If we can't get the issue number exit.
			$this->response->message = 'Hook data does not exist';

			return;
		}

		try
		{
			// If the item is already in the database, update it; else, insert it.
			if ($this->checkIssueExists((int) $this->hookData->issue->number))
			{
				$result = $this->updateData();
			}
			else
			{
				$result = $this->insertData();
			}

			if ($result)
			{
				$this->response->message = 'Hook data processed successfully.';
			}
			else
			{
				$this->response->message = 'Hook data processed unsuccessfully.';
			}
		}
		catch (\Exception $e)
		{
			$logMessage = 'Uncaught Exception processing issue webhook';

			$this->logger->critical($logMessage, ['exception' => $e]);
			$this->setStatusCode(500);
			$this->response->error = $logMessage . ': ' . $e->getMessage();
			$this->response->message = 'Hook data processed unsuccessfully.';
		}
	}

	/**
	 * Method to insert data for an issue from GitHub
	 *
	 * @return  boolean  True on success
	 *
	 * @since   1.0
	 */
	protected function insertData()
	{
		// Figure out the state based on the action
		$action = $this->hookData->action;

		$status = $this->processStatus($action);

		$parsedText = $this->parseText($this->hookData->issue->body);

		// Prepare the dates for insertion to the database
		$dateFormat = $this->db->getDateFormat();

		$data = [];
		$data['issue_number']    = $this->hookData->issue->number;
		$data['title']           = $this->hookData->issue->title;
		$data['description']     = $parsedText;
		$data['description_raw'] = $this->hookData->issue->body;
		$data['status']          = (is_null($status)) ? 1 : $status;
		$data['opened_date']     = (new Date($this->hookData->issue->created_at))->format($dateFormat);
		$data['opened_by']       = $this->hookData->issue->user->login;
		$data['modified_date']   = (new Date($this->hookData->issue->updated_at))->format($dateFormat);
		$data['modified_by']     = $this->hookData->sender->login;
		$data['project_id']      = $this->project->project_id;
		$data['build']           = $this->hookData->repository->default_branch;

		// Add the closed date if the status is closed
		if ($this->hookData->issue->closed_at)
		{
			$data['closed_date'] = (new Date($this->hookData->issue->closed_at))->format($dateFormat);
			$data['closed_by']   = $this->hookData->sender->login;
		}

		// If the title has a [# in it, assume it's a JoomlaCode Tracker ID
		if (preg_match('/\[#([0-9]+)\]/', $this->hookData->issue->title, $matches))
		{
			$data['foreign_number'] = $matches[1];
		}
		// If the body has tracker_item_id= in it, that is a JoomlaCode Tracker ID
		elseif (preg_match('/tracker_item_id=([0-9]+)/', $this->hookData->issue->body, $matches))
		{
			$data['foreign_number'] = $matches[1];
		}

		// Process labels for the item
		$data['labels'] = $this->processLabels($this->hookData->issue->number);

		try
		{
			(new IssueModel($this->db))
				->setProject(new TrackerProject($this->db, $this->project))
				->add($data);
		}
		catch (\Exception $e)
		{
			$logMessage = sprintf(
				'Error adding GitHub issue %s/%s #%d to the tracker',
				$this->project->gh_user,
				$this->project->gh_project,
				$this->hookData->issue->number
			);
			$this->setStatusCode(500);
			$this->response->error = $logMessage . ': ' . $e->getMessage();
			$this->logger->error($logMessage, ['exception' => $e]);

			return false;
		}

		// Get a table object for the new record to process in the event listeners
		$table = (new IssuesTable($this->db))
			->load($this->db->insertid());

		try
		{
			$this->triggerEvent('onIssueAfterCreate', ['table' => $table, 'action' => $action]);
		}
		catch (\Exception $e)
		{
			$logMessage = sprintf(
				'Error processing `onIssueAfterCreate` event for issue number %d',
				$this->hookData->issue->number
			);
			$this->setStatusCode(500);
			$this->response->error = $logMessage . ': ' . $e->getMessage();
			$this->logger->error($logMessage, ['exception' => $e]);

			return false;
		}

		// Pull the user's avatar if it does not exist
		$this->pullUserAvatar($this->hookData->issue->user->login);

		// Add a reopen record to the activity table if the status is closed
		if ($action == 'reopened')
		{
			try
			{
				$this->addActivityEvent(
					'reopen',
					$data['modified_date'],
					$this->hookData->sender->login,
					$this->project->project_id,
					$this->hookData->issue->number
				);
			}
			catch (\RuntimeException $e)
			{
				$logMessage = sprintf(
					'Error storing reopen activity to the database (Project ID: %1$d, Item #: %2$d)',
					$this->project->project_id,
					$this->hookData->issue->number
				);
				$this->setStatusCode(500);
				$this->response->error = $logMessage . ': ' . $e->getMessage();
				$this->logger->error($logMessage, ['exception' => $e]);

				return false;
			}
		}

		// Add a close record to the activity table if the status is closed
		if ($this->hookData->issue->closed_at)
		{
			try
			{
				$this->addActivityEvent(
					'close',
					$data['closed_date'],
					$this->hookData->issue->user->login,
					$this->project->project_id,
					$this->hookData->issue->number
				);
			}
			catch (\RuntimeException $e)
			{
				$logMessage = sprintf(
					'Error storing close activity to the database (Project ID: %1$d, Item #: %2$d)',
					$this->project->project_id,
					$this->hookData->issue->number
				);
				$this->setStatusCode(500);
				$this->response->error = $logMessage . ': ' . $e->getMessage();
				$this->logger->error($logMessage, ['exception' => $e]);

				return false;
			}
		}

		// Store was successful, update status
		$this->logger->info(
			sprintf(
				'Added GitHub issue %s/%s #%d (Database ID #%d) to the tracker.',
				$this->project->gh_user,
				$this->project->gh_project,
				$this->hookData->issue->number,
				$table->id
			)
		);

		return true;
	}

	/**
	 * Method to update data for an issue from GitHub
	 *
	 * @return  boolean  True on success
	 *
	 * @since   1.0
	 */
	protected function updateData()
	{
		$table = new IssuesTable($this->db);

		try
		{
			$table->load(
				[
					'issue_number' => $this->hookData->issue->number,
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
				$this->hookData->issue->number
			);
			$this->setStatusCode(500);
			$this->response->error = $logMessage . ': ' . $e->getMessage();
			$this->logger->error($logMessage, ['exception' => $e]);

			return false;
		}

		$action = $this->hookData->action;

		// Handle an edit a bit differently than a general update
		if ($action === 'edited')
		{
			return $this->editIssue($table);
		}

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
			'status'          => is_null($status) ? $table->status : $status,
			'modified_date'   => (new Date($this->hookData->issue->updated_at))->format($dateFormat),
			'modified_by'     => $this->hookData->sender->login,
			'priority'        => $table->priority,
			'build'           => $table->build,
			'rel_number'      => $table->rel_number,
			'rel_type'        => $table->rel_type,
			'milestone_id'    => $table->milestone_id,
		];

		// Add the closed date if the status is closed
		if ($this->hookData->issue->closed_at)
		{
			$data['closed_date'] = (new Date($this->hookData->issue->closed_at))->format($dateFormat);
		}

		// Process labels for the item
		$data['labels'] = $this->processLabels($this->hookData->issue->number);

		if (empty($data['build']))
		{
			$data['build'] = $this->hookData->repository->default_branch;
		}

		$model = (new IssueModel($this->db))
			->setProject(new TrackerProject($this->db, $this->project));

		// Check if the state has changed (e.g. open/closed)
		$oldState = $model->getOpenClosed($table->status);
		$state    = is_null($status) ? $oldState : $model->getOpenClosed($data['status']);

		$data['old_state'] = $oldState;
		$data['new_state'] = $state;

		try
		{
			$model->save($data);
		}
		catch (\Exception $e)
		{
			$logMessage = sprintf(
				'Error updating GitHub issue %s/%s #%d (Database ID #%d) in the tracker',
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

		try
		{
			$this->triggerEvent('onIssueAfterUpdate', ['table' => $table]);
		}
		catch (\Exception $e)
		{
			$logMessage = sprintf(
				'Error processing `onIssueAfterUpdate` event for issue number %d',
				$this->hookData->issue->number
			);
			$this->setStatusCode(500);
			$this->response->error = $logMessage . ': ' . $e->getMessage();
			$this->logger->error($logMessage, ['exception' => $e]);

			return false;
		}

		// Add a reopen record to the activity table if the status is closed
		if ($action == 'reopened')
		{
			try
			{
				$this->addActivityEvent(
					'reopen',
					$this->hookData->issue->updated_at,
					$this->hookData->sender->login,
					$this->project->project_id,
					$this->hookData->issue->number
				);
			}
			catch (\RuntimeException $e)
			{
				$logMessage = sprintf(
					'Error storing reopen activity to the database (Project ID: %1$d, Item #: %2$d)',
					$this->project->project_id,
					$this->hookData->issue->number
				);
				$this->setStatusCode(500);
				$this->response->error = $logMessage . ': ' . $e->getMessage();
				$this->logger->error($logMessage, ['exception' => $e]);

				return false;
			}
		}

		// Add a close record to the activity table if the status is closed
		if ($this->hookData->issue->closed_at)
		{
			try
			{
				$this->addActivityEvent(
					'close',
					$this->hookData->issue->closed_at,
					$this->hookData->sender->login,
					$this->project->project_id,
					$this->hookData->issue->number
				);
			}
			catch (\RuntimeException $e)
			{
				$logMessage = sprintf(
					'Error storing close activity to the database (Project ID: %1$d, Item #: %2$d)',
					$this->project->project_id,
					$this->hookData->issue->number
				);
				$this->setStatusCode(500);
				$this->response->error = $logMessage . ': ' . $e->getMessage();
				$this->logger->error($logMessage, ['exception' => $e]);

				return false;
			}
		}

		// Store was successful, update status
		$this->logger->info(
			sprintf(
				'Updated GitHub issue %s/%s #%d (Database ID #%d) to the tracker.',
				$this->project->gh_user,
				$this->project->gh_project,
				$this->hookData->issue->number,
				$table->id
			)
		);

		return true;
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

		if (isset($this->hookData->changes->title))
		{
			$data['title'] = $this->hookData->issue->title;
		}

		if (isset($this->hookData->changes->body))
		{
			$data['description']     = $this->parseText($this->hookData->issue->body);
			$data['description_raw'] = $this->hookData->issue->body;
		}

		// Ensure the data array isn't empty for some reason; if it is there's nothing to do here
		if (empty($data))
		{
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
				'title'           => isset($data['title']) ? $data['title'] : $table->title,
				'description'     => isset($data['description']) ? $data['description'] : $table->description,
				'description_raw' => isset($data['description_raw']) ? $data['description_raw'] : $table->description_raw,
				'modified_date'   => (new Date($this->hookData->issue->updated_at))->format($this->db->getDateFormat()),
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
			]
		);

		try
		{
			$model->save($data);
		}
		catch (\Exception $e)
		{
			$logMessage = sprintf(
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

		try
		{
			$this->triggerEvent('onIssueAfterUpdate', ['table' => $table]);
		}
		catch (\Exception $e)
		{
			$logMessage = sprintf(
				'Error processing `onIssueAfterUpdate` event for issue number %d',
				$this->hookData->issue->number
			);
			$this->setStatusCode(500);
			$this->response->error = $logMessage . ': ' . $e->getMessage();
			$this->logger->error($logMessage, ['exception' => $e]);

			return false;
		}

		// Add an edit record to the activity table
		try
		{
			$this->addActivityEvent(
				'edited',
				$this->hookData->issue->updated_at,
				$this->hookData->sender->login,
				$this->project->project_id,
				$this->hookData->issue->number
			);
		}
		catch (\RuntimeException $e)
		{
			$logMessage = sprintf(
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
			sprintf(
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
