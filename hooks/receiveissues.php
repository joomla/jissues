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
	 * The project information of the project whose data has been received
	 *
	 * @var    object
	 * @since  1.0
	 */
	protected $project;

	/**
	 * Method to run the application routines.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function doExecute()
	{
		// Initialize the logger
		$options['format']    = '{DATE}\t{TIME}\t{LEVEL}\t{CODE}\t{MESSAGE}';
		$options['text_file'] = 'github_issues.php';
		JLog::addLogger($options);

		// Initialize the database
		$query = $this->db->getQuery(true);

		// Get the data directly from the $_POST superglobal.  I've yet to make this work with JInput.
		$data = $_POST['payload'];

		// Decode it
		$data = json_decode($data);

		// Get the issue ID
		$githubID = $data->issue->number;

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

		// Get the info for the project on our tracker
		$query->clear();
		$query->select($this->db->quoteName('*'));
		$query->from($this->db->quoteName('#__tracker_projects'));
		$query->where($this->db->quoteName('gh_project') . ' = ' . $this->db->quote($data->repository->name));
		$this->db->setQuery($query);

		try
		{
			$this->project = $this->db->loadObject();
		}
		catch (RuntimeException $e)
		{
			JLog::add(sprintf('Error retrieving the project ID for GitHub repo %s in the database: %s', $data->repository->name, $e->getMessage()), JLog::INFO);
			$this->close();
		}

		// Make sure we have a valid project ID
		if (!$this->project->project_id)
		{
			JLog::add(sprintf('A project does not exist for the %s GitHub repo in the database, cannot add data for it.', $data->repository->name), JLog::INFO);
			$this->close();
		}

		// Instantiate the JTable instance
		$table = JTable::getInstance('Issue');

		// If the item is already in the databse, update it; else, insert it
		if ($issueID)
		{
			$this->updateData($issueID, $data);
		}
		else
		{
			$this->insertData($table, $data);
		}
	}

	/**
	 * Method to insert data for an issue from GitHub
	 *
	 * @param   JTableIssue  $table  Issue table instance
	 * @param   object       $data   The hook data
	 *
	 * @return  boolean  True on success
	 *
	 * @since   1.0
	 */
	protected function insertData(JTableIssue $table, $data)
	{
		// Figure out the state based on the action
		$action = $data->action;

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

		// Get a JGithub instance to parse the body through their parser
		$github = new JGithub;

		// Try to render the description with GitHub markdown
		try
		{
			$issue = $github->markdown->render($data->issue->body, 'gfm', $this->project->gh_user . '/' . $this->project->gh_project);
		}
		catch (DomainException $e)
		{
			JLog::add(sprintf('Error parsing issue text for ID %s with GH Markdown: %s', $issueID, $e->getMessage()), JLog::INFO);
			$this->close();
		}

		$table->gh_id       = $data->issue->number;
		$table->title       = $data->issue->title;
		$table->description = $issue;
		$table->status      = $status;
		$table->opened      = JFactory::getDate($data->issue->created_at)->toSql();
		$table->modified    = JFactory::getDate($data->issue->updated_at)->toSql();
		$table->project_id  = $this->project->project_id;

		// Add the diff URL if this is a pull request
		if ($data->issue->pull_request->diff_url)
		{
			$table->patch_url = $data->issue->pull_request->diff_url;
		}

		// Add the closed date if the status is closed
		if ($data->issue->closed_at)
		{
			$table->closed_date = JFactory::getDate($data->issue->closed_at)->toSql();
		}

		// If the title has a [# in it, assume it's a Joomlacode Tracker ID
		// TODO - Would be better suited as a regex probably
		if (strpos($data->issue->title, '[#') !== false)
		{
			$pos = strpos($data->issue->title, '[#') + 2;
			$table->jc_id = substr($data->issue->title, $pos, 5);
		}

		if (!$table->store())
		{
			JLog::add(sprintf('Error storing new item %s in the database: %s', $data->issue->number, $table->getError()), JLog::INFO);
			$this->close();
		}

		// Get the ID for the new issue
		$query->clear();
		$query->select('id');
		$query->from($this->db->quoteName('#__issues'));
		$query->where($this->db->quoteName('gh_id') . ' = ' . (int) $data->issue->number);
		$this->db->setQuery($query);

		try
		{
			$issueID = $this->db->loadResult();
		}
		catch (RuntimeException $e)
		{
			JLog::add(sprintf('Error retrieving ID for GitHub issue %s in the database: %s', $data->issue->number, $e->getMessage()), JLog::INFO);
			$this->close();
		}

		// Add an open record to the activity table
		$activity = new JTableActivity($this->db);
		$activity->issue_id = (int) $issueID;
		$activity->user     = $data->issue->user->login;
		$activity->event    = 'open';
		$activity->created  = $table->opened;

		if (!$table->store())
		{
			JLog::add(sprintf('Error storing open activity for issue %s in the database: %s', $issueID, $e->getMessage()), JLog::INFO);
			$this->close();
		}

		// Add a reopen record to the activity table if the status is closed
		if ($action == 'reopened')
		{
			$activity = new JTableActivity($this->db);
			$activity->issue_id = (int) $issueID;
			$activity->user     = $data->issue->user->login;
			$activity->event    = 'reopen';
			$activity->created  = $table->modified;

			if (!$table->store())
			{
				JLog::add(sprintf('Error storing reopen activity for issue %s in the database: %s', $issueID, $e->getMessage()), JLog::INFO);
				$this->close();
			}
		}

		// Add a close record to the activity table if the status is closed
		if ($data->issue->closed_at)
		{
			$activity = new JTableActivity($this->db);
			$activity->issue_id = (int) $issueID;
			$activity->user     = $data->issue->user->login;
			$activity->event    = 'close';
			$activity->created  = $table->closed_date;

			if (!$table->store())
			{
				JLog::add(sprintf('Error storing reopen activity for issue %s in the database: %s', $issueID, $e->getMessage()), JLog::INFO);
				$this->close();
			}
		}

		// Store was successful, update status
		JLog::add(sprintf('Added GitHub issue %s to the tracker.', $data->issue->number), JLog::INFO);

		return true;
	}

	/**
	 * Method to update data for an issue from GitHub
	 *
	 * @param   integer  $issueID  Issue ID in the database
	 * @param   object   $data     The hook data
	 *
	 * @return  boolean  True on success
	 *
	 * @since   1.0
	 */
	protected function updateData($issueID, $data)
	{
		// Figure out the state based on the action
		$action = $data->action;

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

		// Get a JGithub instance to parse the body through their parser
		$github = new JGithub;

		// Try to render the description with GitHub markdown
		try
		{
			$issue = $github->markdown->render($data->issue->body, 'gfm', $this->project->gh_user . '/' . $this->project->gh_project);
		}
		catch (DomainException $e)
		{
			JLog::add(sprintf('Error parsing issue text for ID %s with GH Markdown: %s', $issueID, $e->getMessage()), JLog::INFO);
			$this->close();
		}

		// Only update fields that may have changed, there's no API endpoint to show that so make some guesses
		$query = $this->db->getQuery(true);
		$query->update($this->db->quoteName('#__issues'));
		$query->set($this->db->quoteName('title') . ' = ' . $this->db->quote($data->issue->title));
		$query->set($this->db->quoteName('description') . ' = ' . $this->db->quote($issue));
		$query->set($this->db->quoteName('status') . ' = ' . $status);
		$query->set($this->db->quoteName('modified') . ' = ' . $this->db->quote(JFactory::getDate($data->issue->updated_at)->toSql()));
		$query->where($this->db->quoteName('id') . ' = ' . $issueID);

		// Add the closed date if the status is closed
		if ($data->issue->closed_at)
		{
			$query->set($this->db->quoteName('closed_date') . ' = ' . $this->db->quote(JFactory::getDate($data->issue->closed_at)->toSql()));
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
			$activity->user     = $data->issue->user->login;
			$activity->event    = 'reopen';
			$activity->created  = JFactory::getDate($data->issue->updated_at)->toSql();

			if (!$table->store())
			{
				JLog::add(sprintf('Error storing reopen activity for issue %s in the database: %s', $issueID, $e->getMessage()), JLog::INFO);
				$this->close();
			}
		}

		// Add a close record to the activity table if the status is closed
		if ($data->issue->closed_at)
		{
			$activity = new JTableActivity($this->db);
			$activity->issue_id = $issueID;
			$activity->user     = $data->issue->user->login;
			$activity->event    = 'close';
			$activity->created  = JFactory::getDate($data->issue->closed_at)->toSql();

			if (!$table->store())
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
