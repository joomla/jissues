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
const JDEBUG = true;

/**
 * Web application to receive and inject issue comments from GitHub
 *
 * @package     JTracker
 * @subpackage  Hooks
 * @since       1.0
 */
final class TrackerReceiveComments extends JApplicationHooks
{
	/**
	 * Method to run the application routines.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 * @todo    Refactor to work with JTableComment
	 */
	public function doExecute()
	{
		// Register the application to JFactory, just in case
		JFactory::$application = $this;

		// Initialize the logger
		$options['format']    = '{DATE}\t{TIME}\t{LEVEL}\t{CODE}\t{MESSAGE}';
		$options['text_file'] = 'github_comments.php';
		JLog::addLogger($options);

		// Initialize the database
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		// Get the data directly from the $_POST superglobal.  I've yet to make this work with JInput.
		$data = $_POST['payload'];

		// Decode it
		$data = json_decode($data);

		// Get the comment ID
		$commentID = $data->comment->id;

		// Check to see if the comment is already in the database
		$query->select($db->quoteName('id'));
		$query->from($db->quoteName('#__issue_comments'));
		$query->where($db->quoteName('id') . ' = ' . (int) $commentID);
		$db->setQuery($query);

		try
		{
			$comment = $db->loadResult();
		}
		catch (RuntimeException $e)
		{
			JLog::add('Error checking the database for comment ID:' . $e->getMessage(), JLog::INFO);
			$this->close();
		}

		// If the item is already in the databse, update it; else, insert it
		if ($comment)
		{
			$this->updateComment($data);
		}
		else
		{
			$this->insertComment($data);
		}
	}

	/**
	 * Method to insert data for acomment from GitHub
	 *
	 * @param   object  $data  The hook data
	 *
	 * @return  boolean  True on success
	 *
	 * @since   1.0
	 */
	protected function insertComment($data)
	{
		// Initialize the database
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		// First, make sure the issue is already in the database
		$query->select($db->quoteName('id'));
		$query->from($db->quoteName('#__issues'));
		$query->where($db->quoteName('gh_id') . ' = ' . (int) $data->issue->number);
		$db->setQuery($query);

		try
		{
			$issueID = $db->loadResult();
		}
		catch (RuntimeException $e)
		{
			JLog::add('Error checking the database for GitHub ID:' . $e->getMessage(), JLog::INFO);
			$this->close();
		}

		// If we don't have an ID, we need to insert the issue
		if (!$issueID)
		{
			$issueID = $this->insertIssue($data);
		}

		// Store the item in the database
		$columnsArray = array(
			$db->quoteName('id'), $db->quoteName('issue_id'), $db->quoteName('submitter'), $db->quoteName('text'), $db->quoteName('created')
		);

		// Get a JGithub instance to parse the body through their parser
		$github = new JGithub;

		$query->insert($db->quoteName('#__issue_comments'));
		$query->columns($columnsArray);
		$query->values(
			(int) $data->comment->id . ', '
			. (int) $issueID . ', '
			. $db->quote($data->comment->user->login) . ', '
			. $db->quote($github->markdown->render($data->comment->body, 'gfm', 'JTracker/jissues')) . ', '
			. $db->quote(JFactory::getDate($data->comment->created_at)->toSql())
		);
		$db->setQuery($query);

		try
		{
			$db->execute();
		}
		catch (RuntimeException $e)
		{
			$this->out('Error ' . $e->getCode() . ' - ' . $e->getMessage(), true);
			$this->close();
		}

		// Store was successful, update status
		JLog::add(sprintf('Added GitHub comment %s to the tracker.', $data->comment->id), JLog::INFO);

		return true;
	}

	/**
	 * Method to insert data for an issue from GitHub
	 *
	 * @param   object  $data  The hook data
	 *
	 * @return  integer  Issue ID
	 *
	 * @since   1.0
	 */
	protected function insertIssue($data)
	{
		// Get a JGithub instance to parse the body through their parser
		$github = new JGithub;

		$table = JTable::getInstance('Issue');
		$table->gh_id       = $data->issue->number;
		$table->title       = $data->issue->title;
		$table->description = $github->markdown->render($data->issue->body, 'gfm', 'JTracker/jissues');
		$table->status		= ($data->issue->state) == 'open' ? 1 : 10;
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

		return $table->id;
	}

	/**
	 * Method to update data for an issue from GitHub
	 *
	 * @param   object  $data  The hook data
	 *
	 * @return  boolean  True on success
	 *
	 * @since   1.0
	 */
	protected function updateComment($data)
	{
		return true;
	}
}

JApplicationWeb::getInstance('TrackerReceiveComments')->execute();
