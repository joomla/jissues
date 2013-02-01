#!/usr/bin/env php
<?php
/**
 * @package     JTracker
 * @subpackage  Hooks
 *
 * @copyright   Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// Check the request is coming from GitHub
$validIps = array('207.97.227.253', '50.57.128.197', '108.171.174.178', '50.57.231.61');
if (!in_array($_SERVER['REMOTE_ADDR'], $validIps))
{
	die("You don't belong here!");
}

// We are a valid entry point.
const _JEXEC = 1;

// Load system defines
if (file_exists(dirname(__DIR__) . '/defines.php'))
{
	require_once dirname(__DIR__) . '/defines.php';
}

if (!defined('_JDEFINES'))
{
	define('JPATH_BASE', dirname(__DIR__));
	require_once JPATH_BASE . '/includes/defines.php';
}

// Bootstrap the Joomla Platform.
require_once JPATH_LIBRARIES . '/import.legacy.php';

// Bootstrap the CMS libraries.
require_once JPATH_LIBRARIES . '/cms.php';

// Bootstrap the Tracker application libraries.
require_once JPATH_LIBRARIES . '/tracker.php';

// Configure error reporting to maximum for logging.
error_reporting(32767);
ini_set('display_errors', 0);

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

		$table->gh_id       = $data->issue->number;
		$table->title       = $data->issue->title;
		$table->description = $github->markdown->render($data->issue->body, 'gfm', $this->project->gh_user . '/' . $this->project->gh_project);
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

		// Add a open record to the activity table
		$columnsArray = array(
			$this->db->quoteName('issue_id'),
			$this->db->quoteName('user'),
			$this->db->quoteName('event'),
			$this->db->quoteName('created')
		);

		$query->clear();
		$query->insert($this->db->quoteName('#__activity'));
		$query->columns($columnsArray);
		$query->values(
			(int) $issueID . ', '
			. $this->db->quote($data->issue->user->login) . ', '
			. $this->db->quote('open') . ', '
			. $this->db->quote($table->opened)
		);
		$this->db->setQuery($query);

		try
		{
			$this->db->execute();
		}
		catch (RuntimeException $e)
		{
			JLog::add(sprintf('Error storing open activity for issue %s in the database: %s', $issueID, $e->getMessage()), JLog::INFO);
			$this->close();
		}

		// Add a reopen record to the activity table if the status is closed
		if ($action == 'reopened')
		{
			$query->clear('values');
			$query->values(
				(int) $data->issueID . ', '
				. $this->db->quote($data->issue->user->login) . ', '
				. $this->db->quote('reopen') . ', '
				. $this->db->quote($table->modified)
			);
			$this->db->setQuery($query);

			try
			{
				$this->db->execute();
			}
			catch (RuntimeException $e)
			{
				JLog::add(sprintf('Error storing reopen activity for issue %s in the database: %s', $issueID, $e->getMessage()), JLog::INFO);
				$this->close();
			}
		}

		// Add a close record to the activity table if the status is closed
		if ($data->issue->closed_at)
		{
			$query->clear('values');
			$query->values(
				(int) $data->issueID . ', '
				. $this->db->quote($data->issue->user->login) . ', '
				. $this->db->quote('close') . ', '
				. $this->db->quote($table->closed_date)
			);
			$this->db->setQuery($query);

			try
			{
				$this->db->execute();
			}
			catch (RuntimeException $e)
			{
				JLog::add(sprintf('Error storing close activity for issue %s in the database: %s', $issueID, $e->getMessage()), JLog::INFO);
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

		// Only update fields that may have changed, there's no API endpoint to show that so make some guesses
		$query = $this->db->getQuery(true);
		$query->update($this->db->quoteName('#__issues'));
		$query->set($this->db->quoteName('title') . ' = ' . $this->db->quote($data->issue->title));
		$query->set($this->db->quoteName('description') . ' = ' . $this->db->quote($github->markdown->render($data->issue->body, 'gfm', $this->project->gh_user . '/' . $this->project->gh_project)));
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

		// Set up the activity logging
		$columnsArray = array(
			$this->db->quoteName('issue_id'),
			$this->db->quoteName('user'),
			$this->db->quoteName('event'),
			$this->db->quoteName('created')
		);

		$query->clear();
		$query->insert($this->db->quoteName('#__activity'));
		$query->columns($columnsArray);

		// Add a reopen record to the activity table if the status is closed
		if ($action == 'reopened')
		{
			$query->clear('values');
			$query->values(
				(int) $data->issueID . ', '
				. $this->db->quote($data->issue->user->login) . ', '
				. $this->db->quote('reopen') . ', '
				. $this->db->quote($table->modified)
			);
			$this->db->setQuery($query);

			try
			{
				$this->db->execute();
			}
			catch (RuntimeException $e)
			{
				JLog::add(sprintf('Error storing reopen activity for issue %s in the database: %s', $issueID, $e->getMessage()), JLog::INFO);
				$this->close();
			}
		}

		// Add a close record to the activity table if the status is closed
		if ($data->issue->closed_at)
		{
			$query->clear('values');
			$query->values(
				(int) $data->issueID . ', '
				. $this->db->quote($data->issue->user->login) . ', '
				. $this->db->quote('close') . ', '
				. $this->db->quote($table->closed_date)
			);
			$this->db->setQuery($query);

			try
			{
				$this->db->execute();
			}
			catch (RuntimeException $e)
			{
				JLog::add(sprintf('Error storing close activity for issue %s in the database: %s', $issueID, $e->getMessage()), JLog::INFO);
				$this->close();
			}
		}

		// Store was successful, update status
		JLog::add(sprintf('Updated issue %s in the tracker.', $issueID), JLog::INFO);

		return true;
	}
}

JApplicationWeb::getInstance('TrackerReceiveIssues')->execute();
