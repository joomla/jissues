<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Controller\Hooks;

use Joomla\Date\Date;

use App\Tracker\Controller\AbstractHookController;
use App\Tracker\Table\IssuesTable;

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
		// If the item is already in the database, update it; else, insert it.
		if ($this->checkIssueExists((int) $this->hookData->issue->number))
		{
			$this->updateData();
		}
		else
		{
			$this->insertData();
		}

		$this->response->message = 'Hook data processed successfully.';
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
		$opened   = new Date($this->hookData->issue->created_at);
		$modified = new Date($this->hookData->issue->updated_at);

		$data = array();
		$data['issue_number']    = $this->hookData->issue->number;
		$data['title']           = $this->hookData->issue->title;
		$data['description']     = $parsedText;
		$data['description_raw'] = $this->hookData->issue->body;
		$data['status']          = (is_null($status)) ? 1 : $status;
		$data['opened_date']     = $opened->format($dateFormat);
		$data['opened_by']       = $this->hookData->issue->user->login;
		$data['modified_date']   = $modified->format($dateFormat);
		$data['project_id']      = $this->project->project_id;
		$data['build']           = $this->hookData->repository->default_branch;

		// Add the closed date if the status is closed
		if ($this->hookData->issue->closed_at)
		{
			$closed = new Date($this->hookData->issue->closed_at);
			$data['closed_date'] = $closed->format($dateFormat);
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
			$table = new IssuesTable($this->db);
			$table->save($data);
		}
		catch (\Exception $e)
		{
			$this->logger->error(
				sprintf(
					'Error adding GitHub issue %s/%s #%d to the tracker: %s',
					$this->project->gh_user,
					$this->project->gh_project,
					$this->hookData->issue->number,
					$e->getMessage()
				)
			);

			$this->getContainer()->get('app')->close();
		}

		$this->triggerEvent('onIssueAfterCreate', $table);

		// Pull the user's avatar if it does not exist
		$this->pullUserAvatar($this->hookData->issue->user->login);

		// Add a reopen record to the activity table if the status is closed
		if ($action == 'reopened')
		{
			$this->addActivityEvent(
				'reopen',
				$data['modified_date'],
				$this->hookData->sender->login,
				$this->project->project_id,
				$this->hookData->issue->number
			);
		}

		// Add a close record to the activity table if the status is closed
		if ($this->hookData->issue->closed_at)
		{
			$this->addActivityEvent(
				'close',
				$data['closed_date'],
				$this->hookData->issue->user->login,
				$this->project->project_id,
				$this->hookData->issue->number
			);
		}

		// Store was successful, update status
		$this->logger->info(
			sprintf(
				'Added GitHub issue %s/%s #%d to the tracker.',
				$this->project->gh_user,
				$this->project->gh_project,
				$this->hookData->issue->number
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
		// Figure out the state based on the action
		$action = $this->hookData->action;

		$status = $this->processStatus($action);

		// Try to render the description with GitHub markdown
		$parsedText = $this->parseText($this->hookData->issue->body);

		// Prepare the dates for insertion to the database
		$dateFormat = $this->db->getDateFormat();
		$modified   = new Date($this->hookData->issue->updated_at);

		// Only update fields that may have changed, there's no API endpoint to show that so make some guesses
		$data = array();
		$data['title']           = $this->hookData->issue->title;
		$data['description']     = $parsedText;
		$data['description_raw'] = $this->hookData->issue->body;

		if (!is_null($status))
		{
			$data['status']          = $status;
		}

		$data['modified_date']   = $modified->format($dateFormat);
		$data['modified_by']     = $this->hookData->sender->login;

		// Add the closed date if the status is closed
		if ($this->hookData->issue->closed_at)
		{
			$closed = new Date($this->hookData->issue->closed_at);
			$data['closed_date'] = $closed->format($dateFormat);
		}

		// Process labels for the item
		$data['labels'] = $this->processLabels($this->hookData->issue->number);

		try
		{
			$table = new IssuesTable($this->db);
			$table->load(array('issue_number' => $this->hookData->issue->number, 'project_id' => $this->project->project_id));
			$table->save($data);
		}
		catch (\Exception $e)
		{
			$this->logger->error(
				sprintf(
					'Error updating GitHub issue %s/%s #%d in the tracker: %s',
					$this->project->gh_user,
					$this->project->gh_project,
					$this->hookData->issue->number,
					$e->getMessage()
				)
			);

			$this->getContainer()->get('app')->close();
		}

		$this->triggerEvent('onIssueAfterUpdate', $table);

		// Add a reopen record to the activity table if the status is closed
		if ($action == 'reopened')
		{
			$this->addActivityEvent(
				'reopen',
				$this->hookData->issue->updated_at,
				$this->hookData->sender->login,
				$this->project->project_id,
				$this->hookData->issue->number
			);
		}

		// Add a close record to the activity table if the status is closed
		if ($this->hookData->issue->closed_at)
		{
			$this->addActivityEvent(
				'close',
				$this->hookData->issue->closed_at,
				$this->hookData->sender->login,
				$this->project->project_id,
				$this->hookData->issue->number
			);
		}

		// Store was successful, update status
		$this->logger->info(
			sprintf(
				'Updated GitHub issue %s/%s #%d to the tracker.',
				$this->project->gh_user,
				$this->project->gh_project,
				$this->hookData->issue->number
			)
		);

		return true;
	}
}
