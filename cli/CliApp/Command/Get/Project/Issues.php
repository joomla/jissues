<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace CliApp\Command\Get\Project;

use App\Projects\Table\LabelsTable;
use App\Projects\Table\MilestonesTable;
use App\Tracker\Table\IssuesTable;

use CliApp\Command\Get\Project;

use Joomla\Date\Date;

/**
 * Class for retrieving issues from GitHub for selected projects
 *
 * @since  1.0
 */
class Issues extends Project
{
	/**
	 * The command "description" used for help texts.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $description = 'Retrieve issues from GitHub.';

	protected $changedIssueNumbers = array();

	protected $issues = array();

	/**
	 * Execute the command.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		$this->getApplication()->outputTitle('Retrieve Issues');

		$this->logOut('Start retrieve Issues')
			->selectProject()
			->setupGitHub()
			->fetchData()
			->processData()
			->out()
			->logOut('Finished');
	}

	/**
	 * Method to pull the list of issues from GitHub
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	protected function fetchData()
	{
		$issues = array();

		foreach (array('open', 'closed') as $state)
		{
			$this->out(sprintf('Retrieving <b>%s</b> items from GitHub...', $state), false);
			$this->debugOut('For: ' . $this->project->gh_user . '/' . $this->project->gh_project);

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

				$this->checkGitHubRateLimit($this->github->issues->getRateLimitRemaining());

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

		$this->logOut(sprintf('Retrieved <b>%d</b> items from GitHub.', count($issues)));

		$this->issues = $issues;

		return $this;
	}

	/**
	 * Method to process the list of issues and inject into the database as needed
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	protected function processData()
	{
		$ghIssues = $this->issues;
		$dbIssues = $this->getDbIssues();

		if (!$ghIssues)
		{
			throw new \UnexpectedValueException('No issues received...');
		}

		$added = 0;
		$updated = 0;

		$milestones = $this->getMilestones();

		$this->out('Adding issues to the database...', false);

		$progressBar = $this->getProgressBar(count($ghIssues));

		$this->usePBar ? $this->out() : null;

		// Start processing the pulls now
		foreach ($ghIssues as $count => $ghIssue)
		{
			$this->usePBar
				? $progressBar->update($count + 1)
				: $this->out($ghIssue->number . '...', false);

			if (!$this->checkInRange($ghIssue->number))
			{
				// Not in range
				$this->usePBar ? null : $this->out('NiR ', false);
				continue;
			}

			$id = 0;

			foreach ($dbIssues as $dbIssue)
			{
				if ($ghIssue->number == $dbIssue->issue_number)
				{
					if ($this->force)
					{
						// Force update
						$this->usePBar ? null : $this->out('F ', false);
						$id = $dbIssue->id;

						break;
					}

					$d1 = new Date($ghIssue->updated_at);
					$d2 = new Date($dbIssue->modified_date);

					if ($d1 == $d2)
					{
						// No update required
						$this->usePBar ? null : $this->out('- ', false);
						continue 2;
					}

					$id = $dbIssue->id;

					break;
				}
			}

			// Store the item in the database
			$table = new IssuesTable($this->container->get('db'));

			if ($id)
			{
				$table->load($id);
			}

			$table->issue_number = $ghIssue->number;
			$table->title        = $ghIssue->title;

			$table->description = $this->github->markdown->render(
				$ghIssue->body,
				'gfm',
				$this->project->gh_user . '/' . $this->project->gh_project
			);

			$this->checkGitHubRateLimit($this->github->markdown->getRateLimitRemaining());

			$table->description_raw = $ghIssue->body;

			$table->status = ($ghIssue->state == 'open') ? 1 : 10;

			$table->opened_date = (new Date($ghIssue->created_at))->format('Y-m-d H:i:s');
			$table->opened_by   = $ghIssue->user->login;

			$table->modified_date = (new Date($ghIssue->updated_at))->format('Y-m-d H:i:s');
			$table->modified_by   = $ghIssue->user->login;

			$table->project_id = $this->project->project_id;
			$table->milestone_id = ($ghIssue->milestone && isset($milestones[$ghIssue->milestone->number]))
				? $milestones[$ghIssue->milestone->number]
				: null;

			// If the issue has a diff URL, it is a pull request.
			if ($ghIssue->pull_request->diff_url)
			{
				$table->has_code = 1;
			}

			// Add the closed date if the status is closed
			if ($ghIssue->closed_at)
			{
				$table->closed_date = (new Date($ghIssue->closed_at))->format('Y-m-d H:i:s');
			}

			// If the title has a [# in it, assume it's a Joomlacode Tracker ID
			if (preg_match('/\[#([0-9]+)\]/', $ghIssue->title, $matches))
			{
				$table->foreign_number = $matches[1];
			}

			$table->labels = implode(',', $this->getLabelIds($ghIssue->labels));

			$table->store(true);

			if (!$table->id)
			{
				// Bad coder :( - @todo when does this happen ??
				throw new \RuntimeException(
					sprintf(
						'Invalid issue id for issue: %1$d in project id %2$s',
						$ghIssue->number, $this->project->project_id
					)
				);
			}

			/*
			@todo see issue #194
			Add an open record to the activity table
			$activity               = new ActivitiesTable($db);
			$activity->project_id   = $this->project->project_id;
			$activity->issue_number = (int) $table->issue_number;
			$activity->user         = $issue->user->login;
			$activity->event        = 'open';
			$activity->created_date = $table->opened_date;

			$activity->store();

			/ Add a close record to the activity table if the status is closed
			if ($issue->closed_at)
			{
				$activity               = new ActivitiesTable($db);
				$activity->project_id   = $this->project->project_id;
				$activity->issue_number = (int) $table->issue_number;
				$activity->event        = 'close';
				$activity->created_date = $issue->closed_at;

				$activity->store();
			}
			*/

			// Store was successful, update status
			if ($id)
			{
				++ $updated;
			}
			else
			{
				++ $added;
			}

			$this->changedIssueNumbers[] = $ghIssue->number;
		}

		// Output the final result
		$this->out()
			->logOut(sprintf('<ok>%1$d added, %2$d updated.</ok>', $added, $updated));

		return $this;
	}

	/**
	 * Get a set of ids from label names.
	 *
	 * @param   array  $labelObjects  Array of label objects
	 *
	 * @return  array
	 *
	 * @since   1.0
	 */
	private function getLabelIds($labelObjects)
	{
		static $labels = array();

		if (!$labels)
		{
			/* @type \Joomla\Database\DatabaseDriver $db */
			$db = $this->container->get('db');

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

	/**
	 * Get the milestones for the active project.
	 *
	 * @return  array  An associative array of the milestone id's keyed by the Github milestone number.
	 *
	 * @since   1.0
	 */
	private function getMilestones()
	{
		/* @type \Joomla\Database\DatabaseDriver $db */
		$db = $this->getContainer()->get('db');
		$table = new MilestonesTable($db);

		$milestoneList = $db->setQuery(
			$db->getQuery(true)
				->from($db->quoteName($table->getTableName()))
				->select(array('milestone_number', 'milestone_id'))
				->where($db->quoteName('project_id') . ' = ' . $this->project->project_id)
		)->loadAssocList('milestone_number', 'milestone_id');

		return $milestoneList;
	}

	/**
	 * Get an array of changed issue numbers.
	 *
	 * @return array
	 *
	 * @since   1.0
	 */
	public function getChangedIssueNumbers()
	{
		return $this->changedIssueNumbers;
	}

	/**
	 * Method to get the GitHub issues from the database
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	protected function getDbIssues()
	{
		/* @type \Joomla\Database\DatabaseDriver $db */
		$db = $this->getContainer()->get('db');

		$query = $db->getQuery(true);

		$query
			->select($db->quoteName('id'))
			->select($db->quoteName('issue_number'))
			->select($db->quoteName('modified_date'))
			->from($db->quoteName('#__issues'))
			->where($db->quoteName('project_id') . '=' . (int) $this->project->project_id);

		// Issues range selected?
		if ($this->rangeTo != 0 && $this->rangeTo >= $this->rangeFrom)
		{
			$query->where($db->quoteName('issue_number') . ' >= ' . (int) $this->rangeFrom);
			$query->where($db->quoteName('issue_number') . ' <= ' . (int) $this->rangeTo);
		}

		$db->setQuery($query);

		return $db->loadObjectList();
	}
}
