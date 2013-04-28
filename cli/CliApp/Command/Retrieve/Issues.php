<?php
/**
 * @package     JTracker
 * @subpackage  CLI
 *
 * @copyright   Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CliApp\Command\Retrieve;

use CliApp\Application\TrackerApplication;
use Joomla\Date\Date;

use Joomla\Tracker\Components\Tracker\Table\IssuesTable;
use Joomla\Tracker\Components\Tracker\Table\ActivitiesTable;

/**
 * Class Issues.
 *
 * @since  1.0
 */
class Issues extends Retrieve
{
	/**
	 * Constructor.
	 *
	 * @param   TrackerApplication  $application  The application object.
	 */
	public function __construct(TrackerApplication $application)
	{
		$this->application = $application;

		$this->description = 'Retrieve issues from GitHub.';
	}

	/**
	 * Execute the command.
	 *
	 * @return void
	 */
	public function execute()
	{
		$this->application->outputTitle('Retrieve Issues');

		$this->selectProject()
			->setupGitHub()
			->displayGitHubRateLimit();

		// Pull in the data from GitHub
		$issues = $this->getData();

		// Process the issues now
		$this->processIssues($issues);

		$this->out('Finished');
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
		$issues = array();

		foreach (array('open', 'closed') as $state)
		{
			$this->out('Retrieving ' . $state . ' items from GitHub...', false);
			$page = 0;

			do
			{
				$page++;
				$issues_more = $this->github->issues->getListByRepository(
					// Owner
					$this->project->gh_user,
					// Repository
					$this->project->gh_project,
					// Milestone
					null,
					// State [ open | closed ]
					$state,
					// Assignee
					null,
					// Creator
					null,
					// Labels
					null,
					// Sort
					'created',
					// Direction
					'asc',
					// Since
					null,
					// Page
					$page,
					// Count
					100
				);

				$count = is_array($issues_more) ? count($issues_more) : 0;

				$this->out('(' . $count . ')', false);

				if ($count)
				{
					$issues = array_merge($issues, $issues_more);
				}
			}

			while ($count);

			$this->out();
		}

		usort(
			$issues, function ($a, $b)
			{
				return $a->number - $b->number;
			}
		);

		$this->out('Retrieved ' . count($issues) . ' items from GitHub, checking database now.');

		return $issues;
	}

	/**
	 * Method to process the list of issues and inject into the database as needed
	 *
	 * @param   array  $issues  Array containing the issues pulled from GitHub
	 *
	 * @throws \RuntimeException
	 * @return  void
	 *
	 * @since   1.0
	 */
	protected function processIssues($issues)
	{
		// Initialize our database object
		$db = $this->application->getDatabase();
		$query    = $db->getQuery(true);
		$added    = 0;

		$this->out('Adding issues to the database');

		// Start processing the pulls now
		foreach ($issues as $issue)
		{
			$this->out($issue->number . '...', false);

			// First, query to see if the issue is already in the database
			$query->clear();
			$query->select('COUNT(*)');
			$query->from($db->quoteName('#__issues'));
			$query->where($db->quoteName('gh_id') . ' = ' . (int) $issue->number);
			$query->where($db->quoteName('project_id') . ' = ' . (int) $this->project->project_id);
			$db->setQuery($query);

			$result = $db->loadResult();

			// If we have something already, then move on to the next item
			if ($result >= 1)
			{
				$this->out('Already added.', false);
				continue;
			}

			// Store the item in the database
			$table = new IssuesTable($db);

			$table->gh_id = $issue->number;
			$table->title = $issue->title;

			$table->description = $this->github->markdown->render(
				$issue->body,
				'gfm',
				$this->project->gh_user . '/' . $this->project->gh_project
			);

			$table->status = ($issue->state == 'open') ? 1 : 10;

			$date          = new Date($issue->created_at);
			$table->opened = $date->format('Y-m-d H:i:s');

			$date            = new Date($issue->updated_at);
			$table->modified = $date->format('Y-m-d H:i:s');

			$table->project_id = $this->project->project_id;

			// Add the diff URL if this is a pull request
			if ($issue->pull_request->diff_url)
			{
				$table->patch_url = $issue->pull_request->diff_url;
			}

			// Add the closed date if the status is closed
			if ($issue->closed_at)
			{
				$date               = new Date($issue->updated_at);
				$table->closed_date = $date->format('Y-m-d H:i:s');
			}

			// If the title has a [# in it, assume it's a Joomlacode Tracker ID
			// TODO - Would be better suited as a regex probably
			if (strpos($issue->title, '[#') !== false)
			{
				$pos          = strpos($issue->title, '[#') + 2;
				$table->jc_id = substr($issue->title, $pos, 5);
			}

			$table->store();

			// Get the ID for the new issue
			$query->clear();
			$query->select('id');
			$query->from($db->quoteName('#__issues'));
			$query->where($db->quoteName('gh_id') . ' = ' . (int) $issue->number);
			$query->where($db->quoteName('project_id') . ' = ' . (int) $this->project->project_id);
			$db->setQuery($query);

			$issueID = $db->loadResult();

			if (!$issueID)
			{
				// Bad coder :(
				throw new \RuntimeException(
					sprintf(
						'Invalid issue id for issue: %1$d in project id %2$s',
						$issue->number, $this->project->project_id
					)
				);
			}

			// Add an open record to the activity table
			$activity           = new ActivitiesTable($db);
			$activity->issue_id = (int) $issueID;
			$activity->user     = $issue->user->login;
			$activity->event    = 'open';
			$activity->created  = $table->opened;

			$activity->store();

			// Add a close record to the activity table if the status is closed
			if ($issue->closed_at)
			{
				$activity           = new ActivitiesTable($db);
				$activity->issue_id = (int) $issueID;
				$activity->user     = $issue->user->login;
				$activity->event    = 'close';
				$activity->created  = $table->closed_date;

				$activity->store();
			}

			// Store was successful, update status
			$added++;
		}

		// Update the final result
		$this->out('Added ' . $added . ' items to the tracker.', true);
	}
}
