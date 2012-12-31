#!/usr/bin/env php
<?php
/**
 * @package     JTracker
 * @subpackage  CLI
 *
 * @copyright   Copyright (C) 2012 Open Source Matters. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

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

// Configure error reporting to maximum for CLI output.
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * CLI Script to pull comments from GitHub issues and add them to the database
 *
 * NOTE: Since this pulls each GitHub Issue's comments separately and inserts each record to the database separately,
 * this will be a time consuming script.
 *
 * @package     JTracker
 * @subpackage  CLI
 * @since       1.0
 */
class TrackerApplicationComments extends JApplicationCli
{
	/**
	 * Comment data from GitHub
	 *
	 * @var    array
	 * @since  1.0
	 */
	protected $comments = array();

	/**
	 * Database object
	 *
	 * @var    JDatabaseDriver
	 * @since  1.0
	 */
	protected $db;

	/**
	 * Array containing the issues from the database and their GitHub ID
	 *
	 * @var    array
	 * @since  1.0
	 */
	protected $issues;

	/**
	 * Method to run the application routines.
	 *
	 * @return  void
	 */
	protected function doExecute()
	{
		$this->db = JFactory::getDbo();

		// Get the issues and their GitHub ID from the database
		$this->getIssues();

		// Get the comments from GitHub now
		$this->getComments();

		// Process the comments now
		$this->processComments();
	}

	/**
	 * Method to get the comments on items from GitHub
	 *
	 * @return  array  Issue data
	 *
	 * @since   1.0
	 */
	protected function getComments()
	{
		$options = new JRegistry;

		// Ask if the user wishes to authenticate to GitHub.  Advantage is increased rate limit to the API.
		$this->out('Do you wish to authenticate to GitHub? [y]es / [n]o :', false);

		$resp = trim($this->in());

		if ($resp == 'y' || $resp == 'yes')
		{
			// Get the username
			$this->out('Enter your GitHub username :', false);
			$username = trim($this->in());

			// Get the password
			$this->out('Enter your GitHub password :', false);
			$password = trim($this->in());

			// Set the options
			$options->set('api.username', $username);
			$options->set('api.password', $password);
		}

		// Instantiate JGithub
		$github = new JGithub($options);

		try
		{
			foreach ($this->issues as $issue)
			{
				$id = $issue->gh_id;
				$this->out('Retrieving comments for issue #' . $id . ' from GitHub.', true);

				$this->comments[$id] = $github->issues->getComments('joomla', 'joomla-cms', $id);
			}
		}
		// Catch any DomainExceptions and close the script
		catch (DomainException $e)
		{
			$this->out('Error ' . $e->getCode() . ' - ' . $e->getMessage(), true);
			$this->close();
		}

		// Retrieved items, report status
		$this->out('Finished retrieving comments for all issues.', true);
	}

	/**
	 * Method to get the GitHub issues from the database
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	protected function getIssues()
	{
		$rangeFrom = 0;
		$rangeTo   = 0;

		// Limit issues to process
		$this->out('GH issues to process? [a]ll / [r]ange :', false);

		$resp = trim($this->in());

		if ($resp == 'r' || $resp == 'range')
		{
			// Get the first GitHub issue (from)
			$this->out('Enter the first GitHub issue ID to process (from) :', false);
			$rangeFrom = (int) trim($this->in());

			// Get the ending GitHub issue (to)
			$this->out('Enter the latest GitHub issue ID to process (to) :', false);
			$rangeTo = (int) trim($this->in());
		}

		$query = $this->db->getQuery(true);

		$query->select($this->db->quoteName(array('id', 'gh_id')));
		$query->from($this->db->quoteName('#__issues'));
		$query->where($this->db->quoteName('gh_id') . ' IS NOT NULL');

		// Issues range selected?
		if ($rangeTo != 0 && $rangeTo >= $rangeFrom)
		{
			$query->where($this->db->quoteName('gh_id') . ' >= ' . (int) $rangeFrom);
			$query->where($this->db->quoteName('gh_id') . ' <= ' . (int) $rangeTo);
		}

		$this->db->setQuery($query);

		try
		{
			$this->issues = $this->db->loadObjectList();
		}
		catch (RuntimeException $e)
		{
			$this->out('Error ' . $e->getCode() . ' - ' . $e->getMessage(), true);
			$this->close();
		}
	}

	/**
	 * Method to process the list of issues and inject into the database as needed
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	protected function processComments()
	{
		// Initialize our database object
		$query = $this->db->getQuery(true);

		// Start processing the comments now
		foreach ($this->issues as $issue)
		{
			// First, we need to check if the issue is already in the database, we're injecting the GitHub comment ID for that
			foreach ($this->comments[$issue->gh_id] as $comment)
			{
				$query->clear();
				$query->select('COUNT(*)');
				$query->from($this->db->quoteName('#__issue_comments'));
				$query->where($this->db->quoteName('id') . ' = ' . (int) $comment->id);
				$this->db->setQuery($query);

				try
				{
					$result = (int) $this->db->loadResult();
				}
				catch (RuntimeException $e)
				{
					$this->out('Error ' . $e->getCode() . ' - ' . $e->getMessage(), true);
					$this->close();
				}

				// If we have something already, then move on to the next item
				if ($result >= 1)
				{
					continue;
				}

				// Store the item in the database
				$columnsArray = array(
					$this->db->quoteName('id'), $this->db->quoteName('issue_id'), $this->db->quoteName('submitter'), $this->db->quoteName('text'), $this->db->quoteName('created')
				);

				$query->clear();
				$query->insert($this->db->quoteName('#__issue_comments'));
				$query->columns($columnsArray);
				$query->values(
					(int) $comment->id . ', '
					. (int) $issue->id . ', '
					. $this->db->quote($comment->user->login) . ', '
					. $this->db->quote($comment->body) . ', '
					. $this->db->quote(JFactory::getDate($comment->created_at)->toSql())
				);
				$this->db->setQuery($query);

				try
				{
					$this->db->execute();
				}
				catch (RuntimeException $e)
				{
					$this->out('Error ' . $e->getCode() . ' - ' . $e->getMessage(), true);
					$this->close();
				}
			}
			$this->out('Added comments for issue #' . $issue->gh_id . ' from GitHub.', true);
		}
	}
}

JApplicationCli::getInstance('TrackerApplicationComments')->execute();
