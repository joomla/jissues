#!/usr/bin/env php
<?php
/**
 * @package     JTracker
 * @subpackage  Hooks
 *
 * @copyright   Copyright (C) 2012 Open Source Matters. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// Check the request is coming from GitHub
$validIps = array('207.97.227.253', '50.57.128.197', '108.171.174.178');
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

// Bootstrap the Tracker application libraries.
require_once JPATH_LIBRARIES . '/tracker.php';

// Bootstrap the Joomla Platform.
require_once JPATH_LIBRARIES . '/import.legacy.php';

// Bootstrap the CMS libraries.
require_once JPATH_LIBRARIES . '/cms.php';

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
	 * Method to run the application routines.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function doExecute()
	{
		// Register the application to JFactory, just in case
		JFactory::$application = $this;

		// Initialize the logger
		$options['format']    = '{DATE}\t{TIME}\t{LEVEL}\t{CODE}\t{MESSAGE}';
		$options['text_file'] = 'github_issues.php';
		JLog::addLogger($options);

		// Initialize the database
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		// Get the data directly from the $_POST superglobal.  I've yet to make this work with JInput.
		$data = $_POST['payload'];

		// Decode it
		$data = json_decode($data);

		// Get the issue ID
		$githubID = $data->issue->number;

		// Check to see if the issue is already in the database
		$query->select($db->quoteName('id'));
		$query->from($db->quoteName('#__issues'));
		$query->where($db->quoteName('gh_id') . ' = ' . (int) $githubID);
		$db->setQuery($query);

		try
		{
			$issueID = $db->loadResult();
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

		$table = JTable::getInstance('Issue');
		$table->gh_id       = $data->issue->number;
		$table->title       = $data->issue->title;
		$table->description = $data->issue->body;
		$table->status		= $status;
		$table->opened      = JFactory::getDate($data->issue->created_at)->toSql();
		$table->modified    = JFactory::getDate($data->issue->updated_at)->toSql();

		// Hard code the project ID for the tracker project
		$table->project_id  = 43;

		// Add the diff URL if this is a pull request
		if ($data->issue->pull_request->diff_url)
		{
			$table->patch_url = $data->issue->pull_request->diff_url;
		}

		// Add the closed date if the status is closed
		if ($data->issue->closed_at)
		{
			$table->closed_date = $data->issue->closed_at;
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

		// Only update fields that may have changed, there's no API endpoint to show that so make some guesses
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->update($db->quoteName('#__issues'));
		$query->set($db->quoteName('title') . ' = ' . $db->quote($data->issue->title));
		$query->set($db->quoteName('description') . ' = ' . $db->quote($data->issue->body));
		$query->set($db->quoteName('status') . ' = ' . $status);
		$query->set($db->quoteName('modified') . ' = ' . $db->quote(JFactory::getDate($data->issue->updated_at)->toSql()));
		$query->where($db->quoteName('id') . ' = ' . $issueID);

		// Add the closed date if the status is closed
		if ($data->issue->closed_at)
		{
			$query->set($db->quoteName('closed_date') . ' = ' . $db->quote(JFactory::getDate($data->issue->closed_at)->toSql()));
		}

		try
		{
			$db->setQuery($query);
			$db->execute();
		}
		catch (RuntimeException $e)
		{
			JLog::add('Error updating the database for issue ' . $issueID . ':' . $e->getMessage(), JLog::INFO);
			$this->close();
		}

		// Store was successful, update status
		JLog::add(sprintf('Updated issue %s in the tracker.', $issueID), JLog::INFO);

		return true;
	}
}

JApplicationWeb::getInstance('TrackerReceiveIssues')->execute();
