<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Application\Command\Get\Project;

use App\Projects\Table\LabelsTable;
use App\Projects\Table\MilestonesTable;
use App\Tracker\Table\IssuesTable;
use App\Tracker\Table\StatusTable;

use Application\Command\Get\Project;

use Joomla\Date\Date;

use JTracker\Github\DataType\Commit\Status;
use JTracker\Github\GithubFactory;

/**
 * Class for retrieving issues from GitHub for selected projects
 *
 * @since  1.0
 */
class Issues extends Project
{
	/**
	 * List of changed issue numbers.
	 *
	 * @var array
	 *
	 * @since  1.0
	 */
	protected $changedIssueNumbers = array();

	/**
	 * List of issues.
	 *
	 * @var array
	 *
	 * @since  1.0
	 */
	protected $issues = array();

	/**
	 * Github object as a bot account
	 *
	 * @var    \JTracker\Github\Github
	 * @since  1.0
	 */
	protected $githubBot;

	/**
	 * Constructor.
	 *
	 * @since   1.0
	 */
	public function __construct()
	{
		parent::__construct();

		$this->description = g11n3t('Retrieve issues from GitHub.');
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
		$this->getApplication()->outputTitle(g11n3t('Retrieve Issues'));

		// This class has actions that depend on a bot account, fetch a GitHub instance as a bot
		$this->githubBot = GithubFactory::getInstance($this->getApplication(), true);

		$this->logOut(g11n3t('Start retrieve Issues'))
			->selectProject()
			->setupGitHub()
			->fetchData()
			->processData()
			->out()
			->logOut(g11n3t('Finished'));
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
			$this->out(sprintf(g11n3t('Retrieving <b>%s</b> items from GitHub...'), $state), false);
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

		$this->logOut(sprintf(g11n3t('Retrieved <b>%d</b> items from GitHub.'), count($issues)));

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

		$this->out(g11n3t('Adding issues to the database...'), false);

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
			$table = new IssuesTable($this->getContainer()->get('db'));

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

			$statusTable = new StatusTable($this->getContainer()->get('db'));

			// Get the list of status IDs based on the GitHub issue state
			$state = ($ghIssue->state == 'open') ? false : true;

			$stateIds = $statusTable->getStateStatusIds($state);

			// Check if the issue status is in the array; if it is, then the item didn't change open state and we don't need to change the status
			if (!in_array($table->status, $stateIds))
			{
				$table->status = $state ? 10 : 1;
			}

			$table->opened_date = (new Date($ghIssue->created_at))->format('Y-m-d H:i:s');
			$table->opened_by   = $ghIssue->user->login;

			$table->modified_date = (new Date($ghIssue->updated_at))->format('Y-m-d H:i:s');
			$table->modified_by   = $ghIssue->user->login;

			$table->project_id = $this->project->project_id;
			$table->milestone_id = ($ghIssue->milestone && isset($milestones[$ghIssue->milestone->number]))
				? $milestones[$ghIssue->milestone->number]
				: null;

			// If the issue has a diff URL, it is a pull request.
			if (isset($ghIssue->pull_request->diff_url))
			{
				$table->has_code = 1;
				$status = $this->GetMergeStatus($ghIssue);

				if (!$status->state)
				{
					// No status found. Let's create one!

					$status->state = 'pending';
					$status->targetUrl = 'http://issues.joomla.org/gagaga';
					$status->description = 'JTracker Bug Squad working on it...';
					$status->context = 'jtracker';

					// @todo Project based status messages
					// @$this->createStatus($ghIssue, 'pending', 'http://issues.joomla.org/gagaga', 'JTracker Bug Squad working on it...', 'CI/JTracker');
				}
				else
				{
					// Save the merge status to database
					$table->merge_state = $status->state;
					$table->gh_merge_status = json_encode($status);
				}
			}

			// Add the closed date if the status is closed
			if ($ghIssue->closed_at)
			{
				$table->closed_date = (new Date($ghIssue->closed_at))->format('Y-m-d H:i:s');
			}

			// If the title has a [# in it, assume it's a JoomlaCode Tracker ID
			if (preg_match('/\[#([0-9]+)\]/', $ghIssue->title, $matches))
			{
				$table->foreign_number = $matches[1];
			}
			// If the body has tracker_item_id= in it, that is a JoomlaCode Tracker ID
			elseif (preg_match('/tracker_item_id=([0-9]+)/', $ghIssue->body, $matches))
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
			->logOut(sprintf(g11n3t('<ok>%1$d added, %2$d updated.</ok>'), $added, $updated));

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
			$db = $this->getContainer()->get('db');

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
	 * @return  array
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

	/**
	 * Get the GitHub merge status for an issue.
	 *
	 * @param   object  $ghIssue  The issue object.
	 *
	 * @return  Status
	 *
	 * @since   1.0
	 */
	private function getMergeStatus($ghIssue)
	{
		// Get the pull request corresponding to an issue.
		$this->debugOut('Get PR for the issue');

		$pullRequest = $this->github->pulls->get(
			$this->project->gh_user, $this->project->gh_project, $ghIssue->number
		);

		$this->debugOut('Get merge statuses for PR');

		$statuses = $this->github->repositories->statuses->getList(
			$this->project->gh_user, $this->project->gh_project, $pullRequest->head->sha
		);

		$mergeStatus = new Status;

		if (isset($statuses[0]))
		{
			$mergeStatus->state = $statuses[0]->state;
			$mergeStatus->targetUrl = $statuses[0]->target_url;
			$mergeStatus->description = $statuses[0]->description;
			$mergeStatus->context = $statuses[0]->context;
		}

		return $mergeStatus;
	}

	/**
	 * Create a GitHub merge status for the last commit in a PR.
	 *
	 * @param   object  $ghIssue      The issue object.
	 * @param   string  $state        The state (pending, success, error or failure).
	 * @param   string  $targetUrl    Optional target URL.
	 * @param   string  $description  Optional description for the status.
	 * @param   string  $context      A string label to differentiate this status from the status of other systems.
	 *
	 * @return  Status
	 *
	 * @since   1.0
	 */
	private function createStatus($ghIssue, $state, $targetUrl, $description, $context)
	{
		// Get the pull request corresponding to an issue.
		$this->debugOut('Get PR for the issue');

		$pullRequest = $this->githubBot->pulls->get(
			$this->project->gh_user, $this->project->gh_project, $ghIssue->number
		);

		$this->debugOut('Create status for PR');

		return $this->githubBot->repositories->statuses->create(
			$this->project->gh_user, $this->project->gh_project, $pullRequest->head->sha,
			$state, $targetUrl, $description, $context
		);
	}
}
