<?php
/**
 * @copyright  Copyright (C) 2013 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Tracker\Controller\Hooks;

use Joomla\Date\Date;
use Joomla\Log\Log;

use App\Tracker\Controller\AbstractHookController;
use App\Tracker\Table\IssuesTable;

/**
 * Controller class receive and inject issue reports from GitHub.
 *
 *        >>>             !!!   N O T E   !!!                      <<<<<<<<<<<<<<<<<<<   !
 *                        ___________________
 *
 * This is basically the same code as the ReceiveIssuesHook.
 * But since it receives some more info we might use later,
 * I made it a separate class.
 *
 * @todo investigate unification   =;)
 *
 * @since  1.0
 */
class ReceivePullsHook extends AbstractHookController
{
	/**
	 * Data received from GitHub.
	 * @var  object
	 */
	protected $data;

	/**
	 * Execute the controller.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		// Pull or Issue ?
		$this->data = $this->hookData->pull_request;

		// $this->data = $this->hookData->issue;

		$issueID = 0;

		try
		{
			// Check to see if the issue is already in the database
			$issueID = $this->db->setQuery(
				$this->db->getQuery(true)
					->select($this->db->quoteName('id'))
					->from($this->db->quoteName('#__issues'))
					->where($this->db->quoteName('project_id') . ' = ' . (int) $this->project->project_id)
					->where($this->db->quoteName('issue_number') . ' = ' . (int) $this->data->number)
			)->loadResult();
		}
		catch (\RuntimeException $e)
		{
			Log::add('Error checking the database for the GitHub ID:' . $e->getMessage(), Log::INFO);
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

			case 'opened' :
				// Issues: reopened
			case 'reopened' :
				// Pulls: synchronized
			case 'synchronized' :
			default:
				$status = 1;
				break;
		}

		$parsedText = $this->parseText($this->data->body);

		// Prepare the dates for insertion to the database
		$dateFormat = $this->db->getDateFormat();
		$opened = new Date($this->data->created_at);
		$modified   = new Date($this->data->updated_at);

		$table->title         = $this->data->title;
		$table->description   = $parsedText;
		$table->issue_number  = $this->data->number;
		$table->project_id    = $this->project->project_id;
		$table->status        = $status;
		$table->opened_date   = $opened->format($dateFormat);
		$table->opened_by     = $this->data->user->login;
		$table->modified_date = $modified->format($dateFormat);

		// If pull request
		$table->has_code = 1;

		// Add the closed date if the status is closed
		if ($this->data->closed_at)
		{
			$closed = new Date($this->data->closed_at);
			$table->closed_date = $closed->format($dateFormat);
			$table->closed_by   = $this->data->user->login;
		}

		// If the title has a [# in it, assume it's a Joomlacode Tracker ID
		if (preg_match('/\[#([0-9]+)\]/', $this->data->title, $matches))
		{
			$table->foreign_number = $matches[1];
		}

		try
		{
			$table->store();
		}
		catch (\Exception $e)
		{
			Log::add(sprintf('Error storing new item %s in the database: %s', $this->data->number, $e->getMessage()), Log::INFO);
			$this->getApplication()->close();
		}

		// Add an open record to the activity table
		if ('opened' == $action)
		{
			$this->addActivityEvent(
				'open',
				$table->opened_date,
				$this->data->user->login,
				$this->project->project_id,
				$this->data->number
			);
		}

		// Add a reopen record to the activity table if the action is reopened
		if ($action == 'reopened')
		{
			$this->addActivityEvent(
				'reopen',
				$table->modified_date,
				$this->data->user->login,
				$this->project->project_id,
				$this->data->number
			);
		}

		// Add a close record to the activity table if the status is closed
		if ($this->data->closed_at)
		{
			$this->addActivityEvent(
				'close',
				$table->closed_date,
				$this->data->user->login,
				$this->project->project_id,
				$this->data->number
			);
		}

		// Store was successful, update status
		Log::add(
			sprintf(
				'Added GitHub issue %s/%s #%d to the tracker.',
				$this->project->gh_user,
				$this->project->gh_project,
				$this->data->number
			), 	Log::INFO
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
				// Issues: reopened
			case 'reopened' :
				// Pulls: synchronized
			case 'synchronized' :
			default:
				$status = 1;
				break;
		}

		// Try to render the description with GitHub markdown
		$parsedText = $this->parseText($this->data->body);

		// Prepare the dates for insertion to the database
		$dateFormat = $this->db->getDateFormat();
		$modified   = new Date($this->data->updated_at);

		$closed = null;

		// Only update fields that may have changed, there's no API endpoint to show that so make some guesses
		$query = $this->db->getQuery(true);
		$query->update($this->db->quoteName('#__issues'));
		$query->set($this->db->quoteName('title') . ' = ' . $this->db->quote($this->data->title));
		$query->set($this->db->quoteName('description') . ' = ' . $this->db->quote($parsedText));
		$query->set($this->db->quoteName('description_raw') . ' = ' . $this->db->quote($this->data->body));
		$query->set($this->db->quoteName('status') . ' = ' . $status);
		$query->set($this->db->quoteName('modified_date') . ' = ' . $this->db->quote($modified->format($dateFormat)));
		$query->where($this->db->quoteName('issue_number') . ' = ' . $this->data->number);
		$query->where($this->db->quoteName('project_id') . ' = ' . $this->project->project_id);

		// Add the closed date if the status is closed
		if ($this->data->closed_at)
		{
			$closed = new Date($this->data->closed_at);
			$query->set($this->db->quoteName('closed_date') . ' = ' . $this->db->quote($closed->format($dateFormat)));
		}

		try
		{
			$this->db->setQuery($query)
				->execute();
		}
		catch (\RuntimeException $e)
		{
			Log::add('Error updating the database:' . $e->getMessage(), Log::INFO);
			$this->getApplication()->close();
		}

		// Add a reopen record to the activity table if the status is reopened
		if ($action == 'reopened')
		{
			$this->addActivityEvent(
				'reopen',
				$this->data->updated_at,
				$this->data->user->login,
				$this->project->project_id,
				$this->data->number
			);
		}

		// Add a synchronize record to the activity table if the action is synchronized
		if ($action == 'synchronized')
		{
			$this->addActivityEvent(
				'synchronize',
				$this->data->updated_at,
				$this->data->user->login,
				$this->project->project_id,
				$this->data->number
			);
		}

		// Add a close record to the activity table if the status is closed
		if ($this->data->closed_at)
		{
			$this->addActivityEvent(
				'close',
				$this->data->closed_at,
				$this->data->user->login,
				$this->project->project_id,
				$this->data->number
			);
		}

		// Store was successful, update status
		Log::add(
			sprintf(
				'Updated GitHub comment %s/%s #%d to the tracker.',
				$this->project->gh_user,
				$this->project->gh_project,
				$this->data->number
			), Log::INFO
		);

		return true;
	}
}
