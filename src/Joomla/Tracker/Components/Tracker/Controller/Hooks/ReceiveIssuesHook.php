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
use Joomla\Tracker\Components\Tracker\Table\ActivitiesTable;
use Joomla\Tracker\Components\Tracker\Table\IssuesTable;

/**
 * Controller class receive and inject issue reports from GitHub
 *
 * @since  1.0
 */
class ReceiveIssuesHook extends AbstractHookController
{
	/**
	 * Constructor.
	 *
	 * @param   Input                $input  The input object.
	 * @param   AbstractApplication  $app    The application object.
	 *
	 * @since   1.0
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

		// Get the issue ID
		$githubID = $this->hookData->issue->number;

		// Check to see if the issue is already in the database
		$query->select($this->db->quoteName('id'));
		$query->from($this->db->quoteName('#__issues'));
		$query->where($this->db->quoteName('issue_number') . ' = ' . (int) $githubID);
		$this->db->setQuery($query);

		try
		{
			$issueID = $this->db->loadResult();
		}
		catch (\RuntimeException $e)
		{
			Log::add('Error checking the database for the GitHub ID:' . $e->getMessage(), Log::INFO);
			$this->getApplication()->close();
		}

		// Instantiate the IssuesTable instance
		$table = new IssuesTable($this->db);

		// If the item is already in the databse, update it; else, insert it
		if ($issueID)
		{
			$this->updateData($issueID);
		}
		else
		{
			$this->insertData($table);
		}
	}

	/**
	 * Method to insert data for an issue from GitHub
	 *
	 * @param   IssuesTable  $table  Issue table instance
	 *
	 * @return  boolean  True on success
	 *
	 * @since   1.0
	 */
	protected function insertData(IssuesTable $table)
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
		try
		{
			$issue = $this->github->markdown->render($this->hookData->issue->body, 'gfm', $this->project->gh_user . '/' . $this->project->gh_project);
		}
		catch (\DomainException $e)
		{
			Log::add(sprintf('Error parsing issue text for ID %s with GH Markdown: %s', $issueID, $e->getMessage()), Log::INFO);
			$this->getApplication()->close();
		}

		// Prepare the dates for insertion to the database
		$dateFormat = $this->db->getDateFormat();
		$opened     = new Date($this->hookData->issue->created_at);
		$modified   = new Date($this->hookData->issue->updated_at);

		$table->issue_number  = $this->hookData->issue->number;
		$table->title         = $this->hookData->issue->title;
		$table->description   = $issue;
		$table->status        = $status;
		$table->opened_date   = $opened->format($dateFormat);
		$table->modified_date = $modified->format($dateFormat);
		$table->project_id    = $this->project->project_id;

		// Add the diff URL if this is a pull request
		if ($this->hookData->issue->pull_request->diff_url)
		{
			// $table->patch_url = $this->hookData->issue->pull_request->diff_url;
		}

		// Add the closed date if the status is closed
		if ($this->hookData->issue->closed_at)
		{
			$closed = new Date($this->hookData->issue->closed_at);
			$table->closed_date = $closed->format($dateFormat);
		}

		// If the title has a [# in it, assume it's a Joomlacode Tracker ID
		// TODO - Would be better suited as a regex probably
		if (strpos($this->hookData->issue->title, '[#') !== false)
		{
			$pos = strpos($this->hookData->issue->title, '[#') + 2;
			$table->foreign_number = substr($this->hookData->issue->title, $pos, 5);
		}

		try
		{
			$table->store();
		}
		catch (\RuntimeException $e)
		{
			Log::add(sprintf('Error storing new item %s in the database: %s', $this->hookData->issue->number, $e->getMessage()), Log::INFO);
			$this->getApplication()->close();
		}

		// Get the ID for the new issue
		$query = $this->db->getQuery(true);
		$query->select('id');
		$query->from($this->db->quoteName('#__issues'));
		$query->where($this->db->quoteName('issue_number') . ' = ' . (int) $this->hookData->issue->number);
		$this->db->setQuery($query);

		try
		{
			$issueID = $this->db->loadResult();
		}
		catch (\RuntimeException $e)
		{
			Log::add(sprintf('Error retrieving ID for GitHub issue %s in the database: %s', $this->hookData->issue->number, $e->getMessage()), Log::INFO);
			$this->getApplication()->close();
		}

		// Add an open record to the activity table
		$activity = new ActivitiesTable($this->db);
		$activity->issue_number = (int) $issueID;
		$activity->user         = $this->hookData->issue->user->login;
		$activity->event        = 'open';
		$activity->created_date = $table->opened_date;

		try
		{
			$activity->store();
		}
		catch (\RuntimeException $e)
		{
			Log::add(sprintf('Error storing open activity for issue %s in the database: %s', $issueID, $e->getMessage()), Log::INFO);
			$this->getApplication()->close();
		}

		// Add a reopen record to the activity table if the status is closed
		if ($action == 'reopened')
		{
			$activity = new ActivitiesTable($this->db);
			$activity->issue_number = (int) $issueID;
			$activity->user         = $this->hookData->issue->user->login;
			$activity->event        = 'reopen';
			$activity->created_date = $table->modified_date;

			try
			{
				$activity->store();
			}
			catch (\RuntimeException $e)
			{
				Log::add(sprintf('Error storing reopen activity for issue %s in the database: %s', $issueID, $e->getMessage()), Log::INFO);
				$this->getApplication()->close();
			}
		}

		// Add a close record to the activity table if the status is closed
		if ($this->hookData->issue->closed_at)
		{
			$activity = new ActivitiesTable($this->db);
			$activity->issue_number = (int) $issueID;
			$activity->user         = $this->hookData->issue->user->login;
			$activity->event        = 'close';
			$activity->created_date = $table->closed_date;

			try
			{
				$activity->store();
			}
			catch (\RuntimeException $e)
			{
				Log::add(sprintf('Error storing reopen activity for issue %s in the database: %s', $issueID, $e->getMessage()), Log::INFO);
				$this->getApplication()->close();
			}
		}

		// Store was successful, update status
		Log::add(sprintf('Added GitHub issue %s to the tracker.', $this->hookData->issue->number), Log::INFO);

		return true;
	}

	/**
	 * Method to update data for an issue from GitHub
	 *
	 * @param   integer  $issueID  Issue ID in the database
	 *
	 * @return  boolean  True on success
	 *
	 * @since   1.0
	 */
	protected function updateData($issueID)
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
		try
		{
			$issue = $this->github->markdown->render($this->hookData->issue->body, 'gfm', $this->project->gh_user . '/' . $this->project->gh_project);
		}
		catch (\DomainException $e)
		{
			Log::add(sprintf('Error parsing issue text for ID %s with GH Markdown: %s', $issueID, $e->getMessage()), Log::INFO);
			$this->getApplication()->close();
		}

		// Prepare the dates for insertion to the database
		$dateFormat = $this->db->getDateFormat();
		$modified   = new Date($this->hookData->issue->updated_at);

		if ($this->hookData->issue->closed_at)
		{
			$closed = new Date($this->hookData->issue->closed_at);
		}

		// Only update fields that may have changed, there's no API endpoint to show that so make some guesses
		$query = $this->db->getQuery(true);
		$query->update($this->db->quoteName('#__issues'));
		$query->set($this->db->quoteName('title') . ' = ' . $this->db->quote($this->hookData->issue->title));
		$query->set($this->db->quoteName('description') . ' = ' . $this->db->quote($issue));
		$query->set($this->db->quoteName('status') . ' = ' . $status);
		$query->set($this->db->quoteName('modified') . ' = ' . $this->db->quote($modified->format($dateFormat)));
		$query->where($this->db->quoteName('id') . ' = ' . $issueID);

		// Add the closed date if the status is closed
		if ($this->hookData->issue->closed_at)
		{
			$query->set($this->db->quoteName('closed_date') . ' = ' . $this->db->quote($closed->format($dateFormat)));
		}

		try
		{
			$this->db->setQuery($query);
			$this->db->execute();
		}
		catch (\RuntimeException $e)
		{
			Log::add('Error updating the database for issue ' . $issueID . ':' . $e->getMessage(), Log::INFO);
			$this->getApplication()->close();
		}

		// Add a reopen record to the activity table if the status is closed
		if ($action == 'reopened')
		{
			$activity = new ActivitiesTable($this->db);
			$activity->issue_number = $issueID;
			$activity->user         = $this->hookData->issue->user->login;
			$activity->event        = 'reopen';
			$activity->created_date = $modified->format($dateFormat);

			try
			{
				$activity->store();
			}
			catch (\RuntimeException $e)
			{
				Log::add(sprintf('Error storing reopen activity for issue %s in the database: %s', $issueID, $e->getMessage()), Log::INFO);
				$this->getApplication()->close();
			}
		}

		// Add a close record to the activity table if the status is closed
		if ($this->hookData->issue->closed_at)
		{
			$activity = new ActivitiesTable($this->db);
			$activity->issue_number = $issueID;
			$activity->user         = $this->hookData->issue->user->login;
			$activity->event        = 'close';
			$activity->created_date = $closed->format($dateFormat);

			try
			{
				$activity->store();
			}
			catch (\RuntimeException $e)
			{
				Log::add(sprintf('Error storing reopen activity for issue %s in the database: %s', $issueID, $e->getMessage()), Log::INFO);
				$this->getApplication()->close();
			}
		}

		// Store was successful, update status
		Log::add(sprintf('Updated issue %s in the tracker.', $issueID), Log::INFO);

		return true;
	}
}
