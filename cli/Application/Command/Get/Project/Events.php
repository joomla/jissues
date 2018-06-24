<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Application\Command\Get\Project;

use App\Projects\TrackerProject;
use App\Tracker\Table\ActivitiesTable;

use Application\Command\Get\Project;

use Joomla\Date\Date;

/**
 * Class for retrieving events from GitHub for selected projects
 *
 * @since  1.0
 */
class Events extends Project
{
	/**
	 * Event data from GitHub
	 *
	 * @var    array
	 * @since  1.0
	 */
	protected $items = [];

	/**
	 * Constructor.
	 *
	 * @since   1.0
	 */
	public function __construct()
	{
		parent::__construct();

		$this->description = g11n3t('Retrieve issue events from GitHub.');
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
		$this->getApplication()->outputTitle(g11n3t('Retrieve Events'));

		$this->logOut(g11n3t('Start retrieve Events'))
			->selectProject()
			->setupGitHub()
			->fetchData()
			->processData()
			->out()
			->logOut(g11n3t('Finished.'));
	}

	/**
	 * Set the changed issues.
	 *
	 * @param   array  $changedIssueNumbers  List of changed issue numbers.
	 *
	 * @return $this
	 *
	 * @since   1.0
	 */
	public function setChangedIssueNumbers(array $changedIssueNumbers)
	{
		$this->changedIssueNumbers = $changedIssueNumbers;

		return $this;
	}

	/**
	 * Method to get the comments on items from GitHub
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	protected function fetchData()
	{
		if (!$this->changedIssueNumbers)
		{
			return $this;
		}

		$this->out(
			sprintf(
				g11n4t(
					'Fetch events for one issue from GitHub...',
					'Fetch events for <b>%d</b> issues from GitHub...',
					count($this->changedIssueNumbers)
				),
				count($this->changedIssueNumbers)
			), false
		);

		$progressBar = $this->getProgressBar(count($this->changedIssueNumbers));

		$this->usePBar ? $this->out() : null;

		foreach ($this->changedIssueNumbers as $count => $issueNumber)
		{
			$this->usePBar
				? $progressBar->update($count + 1)
				: $this->out(
					sprintf(
						'%d/%d - # %d: ', $count + 1, count($this->changedIssueNumbers), $issueNumber
					),
					false
				);

			$page = 0;
			$this->items[$issueNumber] = [];

			do
			{
				$page++;

				$events = $this->github->issues->events->getList(
					$this->project->gh_user, $this->project->gh_project, $issueNumber, $page, 100
				);

				$this->checkGitHubRateLimit($this->github->issues->events->getRateLimitRemaining());

				$count = is_array($events) ? count($events) : 0;

				if ($count)
				{
					$this->items[$issueNumber] = array_merge($this->items[$issueNumber], $events);

						$this->usePBar
							? null
							: $this->out($count . ' ', false);
				}
			}

			while ($count);
		}

		// Retrieved items, report status
		$this->out()
			->outOK();

		return $this;
	}

	/**
	 * Method to process the list of issues and inject into the database as needed
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 * @throws  \UnexpectedValueException
	 */
	protected function processData()
	{
		if (!$this->items)
		{
			$this->logOut(g11n3t('Everything is up to date.'));

			return $this;
		}

		/** @var \Joomla\Database\DatabaseDriver $db */
		$db = $this->getContainer()->get('db');

		$query = $db->getQuery(true);

		$this->out(g11n3t('Adding events to the database...'), false);

		$progressBar = $this->getProgressBar(count($this->items));

		$this->usePBar ? $this->out() : null;

		$adds = 0;
		$count = 0;

		// Initialize our ActivitiesTable instance to insert the new record
		$table = new ActivitiesTable($db);

		foreach ($this->items as $issueNumber => $events)
		{
			$this->usePBar
				? null
				: $this->out(sprintf(' #%d (%d/%d)...', $issueNumber, $count + 1, count($this->items)), false);

			foreach ($events as $event)
			{
				switch ($event->event)
				{
					case 'referenced' :
					case 'closed' :
					case 'reopened' :
					case 'assigned' :
					case 'unassigned' :
					case 'merged' :
					case 'head_ref_deleted' :
					case 'head_ref_restored' :
					case 'milestoned' :
					case 'demilestoned' :
					case 'renamed' :
					case 'locked' :
					case 'unlocked' :
						$query->clear()
							->select($table->getKeyName())
							->from($db->quoteName('#__activities'))
							->where($db->quoteName('gh_comment_id') . ' = ' . (int) $event->id)
							->where($db->quoteName('project_id') . ' = ' . (int) $this->project->project_id);

						$db->setQuery($query);

						$id = (int) $db->loadResult();

						$table->reset();
						$table->{$table->getKeyName()} = null;

						if ($id && !$this->force)
						{
							if ($this->force)
							{
								// Force update
								$this->usePBar ? null : $this->out('F', false);

								$table->{$table->getKeyName()} = $id;
							}
							else
							{
								// If we have something already, then move on to the next item
								$this->usePBar ? null : $this->out('-', false);

								continue;
							}
						}
						else
						{
							$this->usePBar ? null : $this->out('+', false);
						}

						// Translate GitHub event names to "our" name schema
						$evTrans = [
							'referenced' => 'reference', 'closed' => 'close', 'reopened' => 'reopen',
							'assigned' => 'assigned', 'unassigned' => 'unassigned', 'merged' => 'merge',
							'head_ref_deleted' => 'head_ref_deleted', 'head_ref_restored' => 'head_ref_restored',
							'milestoned' => 'change', 'demilestoned' => 'change', 'labeled' => 'change', 'unlabeled' => 'change',
							'renamed' => 'change', 'locked' => 'locked', 'unlocked' => 'unlocked',
						];

						$table->gh_comment_id = $event->id;
						$table->issue_number  = $issueNumber;
						$table->project_id    = $this->project->project_id;
						$table->user          = $event->actor->login;
						$table->event         = $evTrans[$event->event];
						$table->created_date  = (new Date($event->created_at))->format('Y-m-d H:i:s');

						if ('referenced' == $event->event)
						{
							$table->text_raw = $event->commit_id;
							$table->text     = $table->text_raw;
						}

						if ('assigned' == $event->event)
						{
							$table->text_raw = 'Assigned to ' . $event->assignee->login;
							$table->text     = $table->text_raw;
						}

						if ('unassigned' == $event->event)
						{
							$table->text_raw = $event->assignee->login . ' was unassigned';
							$table->text     = $table->text_raw;
						}

						if ('locked' == $event->event)
						{
							$table->text_raw = $event->actor->login . ' locked the issue';
							$table->text     = $table->text_raw;
						}

						if ('unlocked' == $event->event)
						{
							$table->text_raw = $event->actor->login . ' unlocked the issue';
							$table->text     = $table->text_raw;
						}

						$changes = $this->prepareChanges($event);

						if (!empty($changes))
						{
							$table->text = json_encode($changes);
						}

						$table->store();

						++ $adds;
						break;

					case 'mentioned' :
					case 'subscribed' :
					case 'unsubscribed' :
					case 'labeled' :
					case 'unlabeled' :
						continue;

					default:
						$this->logOut(sprintf('ERROR: Unknown Event: %s', $event->event));
						continue;
				}
			}

			++ $count;

			$this->usePBar
				? $progressBar->update($count)
				: null;
		}

		$this->out()
			->outOK()
			->logOut(sprintf(g11n3t('Added %d new issue events to the database'), $adds));

		return $this;
	}

	/**
	 * Method to prepare the changes for saving.
	 *
	 * @param   object  $event  The issue event
	 *
	 * @return  array  The array of changes for activities list
	 *
	 * @since   1.0
	 */
	private function prepareChanges($event)
	{
		/** @var \Joomla\Database\DatabaseDriver $db */
		$db = $this->getContainer()->get('db');

		$changes = [];

		switch ($event->event)
		{
			case 'milestoned':
				$milestoneId = null;

				$milestones = (new TrackerProject($db, $this->project))
					->getMilestones();

				// Get the id of added milestone
				foreach ($milestones as $milestone)
				{
					if ($event->milestone->title == $milestone->title)
					{
						$milestoneId = $milestone->milestone_id;
					}
				}

				$change = new \stdClass;

				$change->name = 'milestone_id';
				$change->old  = null;
				$change->new  = $milestoneId;
				break;

			case 'demilestoned':
				$milestoneId = null;

				$milestones = (new TrackerProject($db, $this->project))
					->getMilestones();

				// Get the id of removed milestone
				foreach ($milestones as $milestone)
				{
					if ($event->milestone->title == $milestone->title)
					{
						$milestoneId = $milestone->milestone_id;
					}
				}

				$change = new \stdClass;

				$change->name = 'milestone_id';
				$change->old  = $milestoneId;
				$change->new  = null;
				break;

			case 'renamed':
				$change = new \stdClass;

				$change->name = 'title';
				$change->old  = $event->rename->from;
				$change->new  = $event->rename->to;
				break;

			default:
				$change = null;
		}

		if (null !== $change)
		{
			$changes[] = $change;
		}

		return $changes;
	}
}
