<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CliApp\Command\Get;

use App\Projects\Table\LabelsTable;
use CliApp\Application\TrackerApplication;
use Joomla\Date\Date;

use App\Tracker\Table\IssuesTable;
use App\Tracker\Table\ActivitiesTable;

/**
 * Class for retrieving issues from GitHub for selected projects
 *
 * @since  1.0
 */
class Issues extends Get
{
	/**
	 * Constructor.
	 *
	 * @param   TrackerApplication  $application  The application object.
	 *
	 * @since   1.0
	 */
	public function __construct(TrackerApplication $application)
	{
		$this->application = $application;

		$this->description = 'Retrieve issues from GitHub.';

		$this->usePBar = $this->application->get('cli-application.progress-bar');

		if ($this->application->input->get('noprogress'))
		{
			$this->usePBar = false;
		}
	}

	/**
	 * Execute the command.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		$this->application->outputTitle('Retrieve Issues');

		$this->selectProject()
			->setupGitHub()
			->displayGitHubRateLimit();

		// Process the data from GitHub
		$this->processIssues($this->getData());

		$this->out()
			->out('Finished');
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
			$this->out(sprintf('Retrieving <b>%s</b> items from GitHub...', $state), false);
			$page = 0;

			$this->debugOut('For: ' . $this->project->gh_user . '/' . $this->project->gh_project);

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

				if ($count)
				{
					$issues = array_merge($issues, $issues_more);

					$this->out('(' . $count . ')', false);
				}
			}

			while ($count);

			$this->out();
		}

		usort(
			$issues, function ($first, $second)
			{
				return $first->number - $second->number;
			}
		);

		$this->out(sprintf('Retrieved <b>%d</b> items from GitHub.', count($issues)));

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
	 * @throws  \RuntimeException
	 */
	protected function processIssues($issues)
	{
		// Initialize our database object
		$db    = $this->application->getDatabase();
		$query = $db->getQuery(true);
		$added = 0;

		$this->out('Adding issues to the database...', false);

		$progressBar = $this->getProgressBar(count($issues));

		$this->usePBar ? $this->out() : null;

		// Start processing the pulls now
		foreach ($issues as $count => $issue)
		{
			$this->usePBar
				? $progressBar->update($count + 1)
				: $this->out($issue->number . '...', false);

			// First, query to see if the issue is already in the database
			$query->clear();
			$query->select('COUNT(*)');
			$query->from($db->quoteName('#__issues'));
			$query->where($db->quoteName('issue_number') . ' = ' . (int) $issue->number);
			$query->where($db->quoteName('project_id') . ' = ' . (int) $this->project->project_id);
			$db->setQuery($query);

			$result = $db->loadResult();

			// If we have something already, then move on to the next item
			if ($result >= 1)
			{
				$this->usePBar ? null : $this->out('found.', false);
				continue;
			}

			// Store the item in the database
			$table = new IssuesTable($db);

			$table->issue_number = $issue->number;
			$table->title        = $issue->title;

			$table->description = $this->github->markdown->render(
				$issue->body,
				'gfm',
				$this->project->gh_user . '/' . $this->project->gh_project
			);

			$table->description_raw = $issue->body;

			$table->status = ($issue->state == 'open') ? 1 : 10;

			$table->opened_date = with(new Date($issue->created_at))->format('Y-m-d H:i:s');
			$table->opened_by   = $issue->user->login;

			$table->modified_date = with(new Date($issue->updated_at))->format('Y-m-d H:i:s');

			$table->project_id = $this->project->project_id;

			// If the issue has a diff URL, it is a pull request.
			if ($issue->pull_request->diff_url)
			{
				$table->has_code = 1;
			}

			// Add the closed date if the status is closed
			if ($issue->closed_at)
			{
				$table->closed_date = with(new Date($issue->closed_at))->format('Y-m-d H:i:s');
			}

			// If the title has a [# in it, assume it's a Joomlacode Tracker ID
			if (preg_match('/\[#([0-9]+)\]/', $issue->title, $matches))
			{
				$table->foreign_number = $matches[1];
			}

			$table->labels = implode(',', $this->getLabelIds($issue->labels));

			$table->store();

			if (!$table->id)
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
			$activity               = new ActivitiesTable($db);
			$activity->project_id   = $this->project->project_id;
			$activity->issue_number = (int) $table->issue_number;
			$activity->user         = $issue->user->login;
			$activity->event        = 'open';
			$activity->created_date = $table->opened_date;

			$activity->store();

			// Add a close record to the activity table if the status is closed
			if ($issue->closed_at)
			{
				$activity               = new ActivitiesTable($db);
				$activity->project_id   = $this->project->project_id;
				$activity->issue_number = (int) $table->issue_number;
				$activity->event        = 'close';
				$activity->created_date = $issue->closed_at;

				// $activity->user     = $issue->user->login;

				$activity->store();
			}

			// Store was successful, update status
			$added++;
		}

		// Output the final result
		$this->out()
			->out(sprintf('<ok>Added %d items to the tracker.</ok>', $added));
	}

	/**
	 * Get a set of ids from label names.
	 *
	 * @param   array  $labelObjects  Array of label objects
	 *
	 * @return array
	 */
	private function getLabelIds($labelObjects)
	{
		static $labels = array();

		if (!$labels)
		{
			$db = $this->application->getDatabase();

			$table = new LabelsTable($db);

			$labelList = $db ->setQuery(
				$db->getQuery(true)
			->from($db->quoteName($table->getTableName()))
			->select(array('label_id', 'name'))
			->where($db->quoteName('project_id') . ' = ' . $this->project->project_id)
			)->loadObjectList();

			foreach ($labelList as $labelObject)
			{
				$labels[$labelObject->name] = $labelObject->label_id;
			}
		}

		$ids = array();

		foreach ($labelObjects as $label)
		{
			if (!array_key_exists($label->name, $labels))
			{
				// @todo Label does not exist :( - reload labels for the project
			}
			else
			{
				$ids[] = $labels[$label->name];
			}
		}

		return $ids;
	}
}
