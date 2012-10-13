#!/usr/bin/env php
<?php
/**
 * @package     BabDev.Tracker
 * @subpackage  CLI
 *
 * @copyright   Copyright (C) 2012 Michael Babker. All rights reserved.
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

// Get the framework.
require_once JPATH_LIBRARIES . '/import.legacy.php';

// Bootstrap the CMS libraries.
require_once JPATH_LIBRARIES . '/cms.php';

// Bootstrap the Tracker application libraries.
require_once JPATH_LIBRARIES . '/tracker.php';

// Configure error reporting to maximum for CLI output.
error_reporting(E_ALL);
ini_set('display_errors', 1);


/**
 * CLI Script to pull open issues from GitHub and inject them to the database if not already present
 *
 * @package     BabDev.Tracker
 * @subpackage  CLI
 * @since       1.0
 */
class TrackerApplicationRetrieve extends JApplicationCli
{
	/**
	 * Method to run the application routines.
	 *
	 * @return  void
	 */
	protected function doExecute()
	{
		// Pull in the data from GitHub
		$issues = $this->getData();

		// Process the issues now
		$this->processIssues($issues);
	}

	/**
	 * Method to pull the list of issues from GitHub
	 *
	 * @return  array  Issue data
	 *
	 * @since   1.0
	 */
	protected function getData()
	{
		// Instantiate JGithub
		$github = new JGithub;

		try
		{
			$issues = array();
			foreach(array('open', 'closed') as $state)
			{
				$this->out('Retrieving ' . $state . ' items from GitHub.', true);
				$page = 0;
				do
				{
					$page++;
					$issues_more = $github->issues->getListByRepository(
						'joomla',		// Owner
						'joomla-cms',	// Repository
						null,			// Milestone
						$state, 		// State [ open | closed ]
						null, 			// Assignee
						null, 			// Creator
						null,			// Labels
						'created', 		// Sort
						'asc', 			// Direction
						null,			// Since
						$page,			// Page
						100				// Count
						);
					$count = is_array($issues_more) ? count($issues_more) : 0;
					$this->out('Retrieved batch of ' . $count . ' items from GitHub.', true);
					if ($count)
					{
						$issues = array_merge($issues, $issues_more);
					}
				} while ($count);
			}

			usort($issues, function($a,$b) { return $a->number - $b->number; } );
		}
		// Catch any DomainExceptions and close the script
		catch (DomainException $e)
		{
			$this->out('Error ' . $e->getCode() . ' - ' . $e->getMessage(), true);
			$this->close();
		}

		// Retrieved items, report status
		$this->out('Retrieved ' . count($issues) . ' items from GitHub, checking database now.', true);
		return $issues;
	}

	/**
	 * Method to process the list of issues and inject into the database as needed
	 *
	 * @param   array  $issues  Array containing the issues pulled from GitHub
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	protected function processIssues($issues)
	{
		// Initialize our database object
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);
		$added = 0;

		// Start processing the pulls now
		foreach ($issues as $issue)
		{
			// First, query to see if the issue is already in the database
			$query->clear();
			$query->select('COUNT(*)');
			$query->from($db->quoteName('#__issues'));
			$query->where($db->quoteName('gh_id') . ' = ' . (int) $issue->number);
			$db->setQuery($query);

			try
			{
				$result = $db->loadResult();
			}
			catch (RuntimeException $e)
			{
				$this->out('Error ' . $e->getCode() . ' - ' . $e->getMessage(), true);
				$this->close();
			}

			// If we have something already, then move on to the next item
			if ($result >= 1)
			{
				$this->out('GitHub issue #' . $issue->number . ' is already in the tracker.', true);
				continue;
			}

			// Store the item in the database
			$table = JTable::getInstance('Issue');
			$table->gh_id       = $issue->number;
			$table->title       = $issue->title;
			$table->description = str_replace("\n", "<br>", $issue->body);
			$table->status		= ($issue->state == 'open') ? 1 : 10;
			$table->opened      = JFactory::getDate($issue->created_at)->toSql();

			// Add the diff URL if this is a pull request
			if ($issue->pull_request->diff_url)
			{
				$table->patch_url = $issue->pull_request->diff_url;
			}

			// Add the closed date if the status is closed
			if ($issue->closed_at)
			{
				$table->closed_date = $issue->closed_at;
			}

			// If the title has a [# in it, assume it's a Joomlacode Tracker ID
			// TODO - Would be better suited as a regex probably
			if (strpos($issue->title, '[#') !== false)
			{
				$pos = strpos($issue->title, '[#') + 2;
				$table->jc_id = substr($issue->title, $pos, 5);
			}

			if (!$table->store())
			{
				$this->out($table->getError(), true);
				$this->close();
			}

			// Store was successful, update status
			$this->out('Added GitHub issue #' . $issue->number . ' to the tracker.', true);
			$added++;
		}

		// Update the final result
		$this->out('Added ' . $added . ' items to the tracker.', true);
	}
}

JApplicationCli::getInstance('TrackerApplicationRetrieve')->execute();
