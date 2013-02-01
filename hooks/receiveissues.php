#!/usr/bin/env php
<?php
/**
 * @package     JTracker
 * @subpackage  Hooks
 *
 * @copyright   Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// We are a valid entry point.
const _JEXEC = 1;

// Bootstrap the hook application
require_once __DIR__ . '/bootstrap.php';

/**
 * Web application to receive and inject issue reports from GitHub
 *
 * @package     JTracker
 * @subpackage  Hooks
 * @since       1.0
 */
final class TrackerReceiveIssues extends JApplicationHooks
{
	/**
	 * Class constructor.
	 *
	 * @since   1.0
	 */
	public function __construct()
	{
		// Set the type of hook
		$this->hookType = 'issues';

		// Run the parent constructor
		parent::__construct();

		// Get the project data
		$this->getProjectData();
	}

	/**
	 * Method to run the application routines.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function doExecute()
	{
		// Initialize the database
		$query = $this->db->getQuery(true);

		// Get the issue ID
		$githubID = $this->hookData->issue->number;

		// Check to see if the issue is already in the database
		$query->select($this->db->quoteName('id'));
		$query->from($this->db->quoteName('#__issues'));
		$query->where($this->db->quoteName('gh_id') . ' = ' . (int) $githubID);
		$this->db->setQuery($query);

		try
		{
			$issueID = $this->db->loadResult();
		}
		catch (RuntimeException $e)
		{
			JLog::add('Error checking the database for the GitHub ID:' . $e->getMessage(), JLog::INFO);
			$this->close();
		}

		// Instantiate the JTable instance
		$table = JTable::getInstance('Issue');

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
	 * @param   JTableIssue  $table  Issue table instance
	 *
	 * @return  boolean  True on success
	 *
	 * @since   1.0
	 */
	protected function insertData(JTableIssue $table)
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
		catch (DomainException $e)
		{
			JLog::add(sprintf('Error parsing issue text for ID %s with GH Markdown: %s', $issueID, $e->getMessage()), JLog::INFO);
			$this->close();
		}

		$table->gh_id       = $this->hookData->issue->number;
		$table->title       = $this->hookData->issue->title;
		$table->description = $issue;
		$table->status      = $status;
		$table->opened      = JFactory::getDate($this->hookData->issue->created_at)->toSql();
		$table->modified    = JFactory::getDate($this->hookData->issue->updated_at)->toSql();
		$table->project_id  = $this->project->project_id;

		// Add the diff URL if this is a pull request
		if ($this->hookData->issue->pull_request->diff_url)
		{
			$table->patch_url = $this->hookData->issue->pull_request->diff_url;
		}

		// Add the closed date if the status is closed
		if ($this->hookData->issue->closed_at)
		{
			$table->closed_date = JFactory::getDate($this->hookData->issue->closed_at)->toSql();
		}

		// If the title has a [# in it, assume it's a Joomlacode Tracker ID
		// TODO - Would be better suited as a regex probably
		if (strpos($this->hookData->issue->title, '[#') !== false)
		{
			$pos = strpos($this->hookData->issue->title, '[#') + 2;
			$table->jc_id = substr($this->hookData->issue->title, $pos, 5);
		}

		if (!$table->store())
		{
			JLog::add(sprintf('Error storing new item %s in the database: %s', $this->hookData->issue->number, $table->getError()), JLog::INFO);
			$this->close();
		}

		// Get the ID for the new issue
		$query->clear();
		$query->select('id');
		$query->from($this->db->quoteName('#__issues'));
		$query->where($this->db->quoteName('gh_id') . ' = ' . (int) $this->hookData->issue->number);
		$this->db->setQuery($query);

		try
		{
			$issueID = $this->db->loadResult();
		}
		catch (RuntimeException $e)
		{
			JLog::add(sprintf('Error retrieving ID for GitHub issue %s in the database: %s', $this->hookData->issue->number, $e->getMessage()), JLog::INFO);
			$this->close();
		}

		// Add an open record to the activity table
		$activity = new JTableActivity($this->db);
		$activity->issue_id = (int) $issueID;
		$activity->user     = $this->hookData->issue->user->login;
		$activity->event    = 'open';
		$activity->created  = $table->opened;

		if (!$activity->store())
		{
			JLog::add(sprintf('Error storing open activity for issue %s in the database: %s', $issueID, $e->getMessage()), JLog::INFO);
			$this->close();
		}

		// Add a reopen record to the activity table if the status is closed
		if ($action == 'reopened')
		{
			$activity = new JTableActivity($this->db);
			$activity->issue_id = (int) $issueID;
			$activity->user     = $this->hookData->issue->user->login;
			$activity->event    = 'reopen';
			$activity->created  = $table->modified;

			if (!$activity->store())
			{
				JLog::add(sprintf('Error storing reopen activity for issue %s in the database: %s', $issueID, $e->getMessage()), JLog::INFO);
				$this->close();
			}
		}

		// Add a close record to the activity table if the status is closed
		if ($this->hookData->issue->closed_at)
		{
			$activity = new JTableActivity($this->db);
			$activity->issue_id = (int) $issueID;
			$activity->user     = $this->hookData->issue->user->login;
			$activity->event    = 'close';
			$activity->created  = $table->closed_date;

			if (!$activity->store())
			{
				JLog::add(sprintf('Error storing reopen activity for issue %s in the database: %s', $issueID, $e->getMessage()), JLog::INFO);
				$this->close();
			}
		}

		// Store was successful, update status
		JLog::add(sprintf('Added GitHub issue %s to the tracker.', $this->hookData->issue->number), JLog::INFO);

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
		catch (DomainException $e)
		{
			JLog::add(sprintf('Error parsing issue text for ID %s with GH Markdown: %s', $issueID, $e->getMessage()), JLog::INFO);
			$this->close();
		}

		// Only update fields that may have changed, there's no API endpoint to show that so make some guesses
		$query = $this->db->getQuery(true);
		$query->update($this->db->quoteName('#__issues'));
		$query->set($this->db->quoteName('title') . ' = ' . $this->db->quote($this->hookData->issue->title));
		$query->set($this->db->quoteName('description') . ' = ' . $this->db->quote($issue));
		$query->set($this->db->quoteName('status') . ' = ' . $status);
		$query->set($this->db->quoteName('modified') . ' = ' . $this->db->quote(JFactory::getDate($this->hookData->issue->updated_at)->toSql()));
		$query->where($this->db->quoteName('id') . ' = ' . $issueID);

		// Add the closed date if the status is closed
		if ($this->hookData->issue->closed_at)
		{
			$query->set($this->db->quoteName('closed_date') . ' = ' . $this->db->quote(JFactory::getDate($this->hookData->issue->closed_at)->toSql()));
		}

		try
		{
			$this->db->setQuery($query);
			$this->db->execute();
		}
		catch (RuntimeException $e)
		{
			JLog::add('Error updating the database for issue ' . $issueID . ':' . $e->getMessage(), JLog::INFO);
			$this->close();
		}

		// Add a reopen record to the activity table if the status is closed
		if ($action == 'reopened')
		{
			$activity = new JTableActivity($this->db);
			$activity->issue_id = $issueID;
			$activity->user     = $this->hookData->issue->user->login;
			$activity->event    = 'reopen';
			$activity->created  = JFactory::getDate($this->hookData->issue->updated_at)->toSql();

			if (!$activity->store())
			{
				JLog::add(sprintf('Error storing reopen activity for issue %s in the database: %s', $issueID, $e->getMessage()), JLog::INFO);
				$this->close();
			}
		}

		// Add a close record to the activity table if the status is closed
		if ($this->hookData->issue->closed_at)
		{
			$activity = new JTableActivity($this->db);
			$activity->issue_id = $issueID;
			$activity->user     = $this->hookData->issue->user->login;
			$activity->event    = 'close';
			$activity->created  = JFactory::getDate($this->hookData->issue->closed_at)->toSql();

			if (!$activity->store())
			{
				JLog::add(sprintf('Error storing reopen activity for issue %s in the database: %s', $issueID, $e->getMessage()), JLog::INFO);
				$this->close();
			}
		}

		// Store was successful, update status
		JLog::add(sprintf('Updated issue %s in the tracker.', $issueID), JLog::INFO);

		return true;
	}
}

JApplicationWeb::getInstance('TrackerReceiveIssues')->execute();
