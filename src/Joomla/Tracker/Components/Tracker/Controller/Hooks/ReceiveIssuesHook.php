<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tracker\Components\Tracker\Controller\Hooks;

use Joomla\Application\AbstractApplication;
use Joomla\Date\Date;
use Joomla\Input\Input;
use Joomla\Log\Log;
use Joomla\Tracker\Components\Tracker\Controller\AbstractHookController;
use Joomla\Tracker\Components\Tracker\Table\IssuesTable;

/**
 * Controller class receive and inject issue reports from GitHub
 *
 * @package     JTracker
 * @subpackage  Hooks
 * @since       1.0
 */
class ReceiveIssuesHook extends AbstractHookController
{
	/**
	 * Constructor.
	 *
	 * @param   Input                $input  The input object.
	 * @param   AbstractApplication  $app    The application object.
	 *
	 * @since  1.0
	 */
	public function __construct(Input $input = null, AbstractApplication $app = null)
	{
		// Set the type of hook
		$this->hookType = 'issues';

		// Run the parent constructor
		parent::__construct($input, $app);

		// Get the project data
		$this->getProjectData();
	}

	/**
	 * Execute the controller.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		// Initialize the database
		$query = $this->db->getQuery(true);

		// Check to see if the issue is already in the database
		$query->select($this->db->quoteName('id'));
		$query->from($this->db->quoteName('#__issues'));
		$query->where($this->db->quoteName('project_id') . ' = ' . (int) $this->project->project_id);
		$query->where($this->db->quoteName('issue_number') . ' = ' . (int) $this->hookData->issue->number);
		$this->db->setQuery($query);

		$issueID = 0;

		try
		{
			$issueID = $this->db->loadResult();
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

			case 'opened':
			case 'reopened':
			default:
				$status = 1;
				break;
		}

		$parsedText = $this->parseText($this->hookData->issue->body);

		// Prepare the dates for insertion to the database
		$dateFormat = $this->db->getDateFormat();
		$opened = new Date($this->hookData->issue->created_at);
		$modified   = new Date($this->hookData->issue->updated_at);

		$table->title         = $this->hookData->issue->title;
		$table->description   = $parsedText;
		$table->issue_number  = $this->hookData->issue->number;
		$table->project_id    = $this->project->project_id;
		$table->status        = $status;
		$table->opened_date   = $opened->format($dateFormat);
		$table->opened_by     = $this->hookData->issue->user->login;
		$table->modified_date = $modified->format($dateFormat);

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
			Log::add(sprintf('Error storing new item %s in the database: %s', $this->hookData->issue->number, $e->getMessage()), Log::INFO);
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
		Log::add(sprintf('Added GitHub issue %s to the tracker.', $this->hookData->issue->number), Log::INFO);

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
			Log::add('Error updating the database:' . $e->getMessage(), Log::INFO);
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
		Log::add(sprintf('Updated issue %s in the tracker.', $this->hookData->issue->number), Log::INFO);

		return true;
	}
}
