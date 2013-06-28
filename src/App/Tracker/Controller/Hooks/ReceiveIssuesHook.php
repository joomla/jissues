<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
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
	 * Execute the controller.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		$issueID = 0;

		// Check to see if the issue is already in the database
		try
		{
			$issueID = $this->db->setQuery(
				$this->db->getQuery(true)
					->select($this->db->quoteName('id'))
					->from($this->db->quoteName('#__issues'))
					->where($this->db->quoteName('project_id') . ' = ' . (int) $this->project->project_id)
					->where($this->db->quoteName('issue_number') . ' = ' . (int) $this->hookData->issue->number)
			)->loadResult();
		}
		catch (\RuntimeException $e)
		{
			$this->logger->error('Error checking the database for the GitHub ID:' . $e->getMessage());
			$this->getApplication()->close();
		}

		// If the item is already in the database, update it; else, insert it.
		if ($issueID)
		{
			$this->updateData();
		}
		else
		{
			$this->insertData();
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
		$table = new IssuesTable($this->db);

		// Figure out the state based on the action
		$action = $this->hookData->action;

		switch ($action)
		{
			case 'closed':
				$status = 10;
				break;

			case 'opened':
			case 'reopened':
			default:
				$status = 1;
				break;
		}

		$parsedText = $this->parseText($this->hookData->issue->body);

		// Prepare the dates for insertion to the database
		$dateFormat = $this->db->getDateFormat();
		$opened   = new Date($this->hookData->issue->created_at);
		$modified = new Date($this->hookData->issue->updated_at);

		$table->title           = $this->hookData->issue->title;
		$table->description     = $parsedText;
		$table->description_raw = $parsedText;
		$table->issue_number    = $this->hookData->issue->number;
		$table->project_id      = $this->project->project_id;
		$table->status          = $status;
		$table->opened_date     = $opened->format($dateFormat);
		$table->opened_by       = $this->hookData->issue->user->login;
		$table->modified_date   = $modified->format($dateFormat);

		// Add the closed date if the status is closed
		if ($this->hookData->issue->closed_at)
		{
			$closed = new Date($this->hookData->issue->closed_at);
			$table->closed_date = $closed->format($dateFormat);
			$table->closed_by   = $this->hookData->issue->user->login;
		}

		// If the title has a [# in it, assume it's a Joomlacode Tracker ID
		if (preg_match('/\[#([0-9]+)\]/', $this->hookData->issue->title, $matches))
		{
			$table->foreign_number = $matches[1];
		}

		try
		{
			$table->store();
		}
		catch (\Exception $e)
		{
			$this->logger->error(
				sprintf(
					'Error storing new item %s in the database: %s',
					$this->hookData->issue->number,
					$e->getMessage()
				)
			);

			$this->getApplication()->close();
		}

		// Add an open record to the activity table
		if ('opened' == $action)
		{
			$this->addActivityEvent(
				'open',
				$table->opened_date,
				$this->hookData->issue->user->login,
				$this->project->project_id,
				$this->hookData->issue->number
			);
		}

		// Add a reopen record to the activity table if the status is closed
		if ($action == 'reopened')
		{
			$this->addActivityEvent(
				'reopen',
				$table->modified_date,
				$this->hookData->issue->user->login,
				$this->project->project_id,
				$this->hookData->issue->number
			);
		}

		// Add a close record to the activity table if the status is closed
		if ($this->hookData->issue->closed_at)
		{
			$this->addActivityEvent(
				'close',
				$table->closed_date,
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

		switch ($action)
		{
			case 'closed':
				$status = 10;
				break;

			case 'opened':
			case 'reopened':
			default:
				$status = 1;
				break;
		}

		// Try to render the description with GitHub markdown
		$parsedText = $this->parseText($this->hookData->issue->body);

		// Prepare the dates for insertion to the database
		$dateFormat = $this->db->getDateFormat();
		$modified   = new Date($this->hookData->issue->updated_at);

		$closed = null;

		// Only update fields that may have changed, there's no API endpoint to show that so make some guesses
		$query = $this->db->getQuery(true);
		$query->update($this->db->quoteName('#__issues'));
		$query->set($this->db->quoteName('title') . ' = ' . $this->db->quote($this->hookData->issue->title));
		$query->set($this->db->quoteName('description') . ' = ' . $this->db->quote($parsedText));
		$query->set($this->db->quoteName('description_raw') . ' = ' . $this->db->quote($this->hookData->issue->body));
		$query->set($this->db->quoteName('status') . ' = ' . $status);
		$query->set($this->db->quoteName('modified_date') . ' = ' . $this->db->quote($modified->format($dateFormat)));
		$query->where($this->db->quoteName('issue_number') . ' = ' . $this->hookData->issue->number);
		$query->where($this->db->quoteName('project_id') . ' = ' . $this->project->project_id);

		// Add the closed date if the status is closed
		if ($this->hookData->issue->closed_at)
		{
			$closed = new Date($this->hookData->issue->closed_at);
			$query->set($this->db->quoteName('closed_date') . ' = ' . $this->db->quote($closed->format($dateFormat)));
		}

		try
		{
			$this->db->setQuery($query)
				->execute();
		}
		catch (\RuntimeException $e)
		{
			$this->logger->error('Error updating the database:' . $e->getMessage());
			$this->getApplication()->close();
		}

		// Add a reopen record to the activity table if the status is closed
		if ($action == 'reopened')
		{
			$this->addActivityEvent(
				'reopen',
				$this->hookData->issue->updated_at,
				$this->hookData->issue->user->login,
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
				$this->hookData->issue->user->login,
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
