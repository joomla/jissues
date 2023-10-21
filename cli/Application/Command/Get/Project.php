<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Application\Command\Get;

use Application\Command\Get\Project\Comments;
use Application\Command\Get\Project\Events;
use Application\Command\Get\Project\Issues;
use Application\Command\Get\Project\Labels;
use Application\Command\Get\Project\Milestones;
use Application\Command\TrackerCommandOption;

/**
 * Class for retrieving issues from GitHub for selected projects
 *
 * @since  1.0
 */
abstract class Project extends Get
{
	/**
	 * Lowest issue to fetch.
	 *
	 * @var    integer
	 * @since  1.0
	 */
	protected $rangeFrom = 0;

	/**
	 * Highest issue to fetch.
	 *
	 * @var    integer
	 * @since  1.0
	 */
	protected $rangeTo = 0;

	/**
	 * List of changed issue numbers.
	 *
	 * @var array
	 *
	 * @since  1.0
	 */
	protected $changedIssueNumbers = [];

	/**
	 * Force update.
	 *
	 * @var boolean
	 *
	 * @since  1.0
	 */
	protected $force = false;

	/**
	 * Constructor.
	 *
	 * @since   1.0
	 */
	public function __construct()
	{
		parent::__construct();

		$this->description = 'Get the whole project info from GitHub, including issues and issue comments.';

		$this
			->addOption(
				new TrackerCommandOption(
					'all',
					'',
					'Process all issues.'
				)
			)
			->addOption(
				new TrackerCommandOption(
					'issue',
					'',
					'<n> Process only a single issue.'
				)
			)
			->addOption(
				new TrackerCommandOption(
					'range_from',
					'',
					'<n> First issue to process.'
				)
			)
			->addOption(
				new TrackerCommandOption(
					'range_to',
					'',
					'<n> Last issue to process.'
				)
			)
			->addOption(
				new TrackerCommandOption(
					'force',
					'f',
					'Force an update even if the issue has not changed.'
				)
			);
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
		$this->getApplication()->outputTitle('Retrieve Project');

		$this->logOut('---- Bulk Start retrieve Project');

		$this->selectProject();

		$this
			->setParams()
			->selectRange()
			->setupGitHub()
			->displayGitHubRateLimit()
			->out(
				strtr(
					'Updating project info for project: %user%/%project%',
					['%user%' => $this->project->gh_user, '%project%' => $this->project->gh_project]
				)
			)
			->processLabels()
			->processMilestones()
			->processIssues()
			->processComments()
			->processEvents()
			->processAvatars()
			->out()
			->logOut('---- Bulk Finished');
	}

	/**
	 * Set internal parameters from the input..
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	protected function setParams()
	{
		$this->force = $this->getOption('force');

		$this->usePBar = $this->getApplication()->get('cli-application.progress-bar');

		if ($this->getOption('noprogress'))
		{
			$this->usePBar = false;
		}

		return $this;
	}

	/**
	 * Process the project labels.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	protected function processLabels()
	{
		(new Labels)
			->setContainer($this->getContainer())
			->execute();

		return $this;
	}

	/**
	 * Process the project milestones.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	protected function processMilestones()
	{
		(new Milestones)
			->setContainer($this->getContainer())
			->execute();

		return $this;
	}

	/**
	 * Process the project issues.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	protected function processIssues()
	{
		$issues = new Issues;

		$issues->rangeFrom = $this->rangeFrom;
		$issues->rangeTo   = $this->rangeTo;
		$issues->force     = $this->force;
		$issues->usePBar   = $this->usePBar;

		$issues->setContainer($this->getContainer());

		$issues->execute();

		$this->changedIssueNumbers = $issues->getChangedIssueNumbers();

		return $this;
	}

	/**
	 * Process the project comments.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	protected function processComments()
	{
		$comments = new Comments;

		$comments->usePBar = $this->usePBar;
		$comments->force   = $this->force;

		$comments
			->setContainer($this->getContainer())
			->setChangedIssueNumbers($this->changedIssueNumbers)
			->execute();

		return $this;
	}

	/**
	 * Process the project events.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	protected function processEvents()
	{
		$events = new Events;

		$events->usePBar = $this->usePBar;

		$events
			->setContainer($this->getContainer())
			->setChangedIssueNumbers($this->changedIssueNumbers)
			->execute();

		return $this;
	}

	/**
	 * Process the project avatars.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	protected function processAvatars()
	{
		(new Avatars)
			->setContainer($this->getContainer())
			->execute();

		return $this;
	}

	/**
	 * Select the range of issues to process.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	protected function selectRange()
	{
		$issue = (integer) $this->getOption('issue');

		$rangeFrom = (integer) $this->getOption('range_from');
		$rangeTo   = (integer) $this->getOption('range_to');

		if ($this->getOption('all'))
		{
			// Process all issues - do nothing
		}
		elseif ($issue)
		{
			// Process only a single issue
			$this->rangeFrom = $issue;
			$this->rangeTo   = $issue;
		}
		elseif ($rangeFrom && $rangeTo)
		{
			// Process a range of issues
			$this->rangeFrom = $rangeFrom;
			$this->rangeTo   = $rangeTo;
		}
		else
		{
			// Select what to process
			$this->out('<question>GitHub issues to process?</question>')
				->out()
				->out('1) All')
				->out('2) Range')
				->out('Select: ', false);

			$resp = trim($this->getApplication()->in());

			if ((int) $resp == 2)
			{
				// Get the first GitHub issue (from)
				$this->out('<question>Enter the first GitHub issue ID to process (from):</question> ', false);
				$this->rangeFrom = (int) trim($this->getApplication()->in());

				// Get the ending GitHub issue (to)
				$this->out('<question>Enter the latest GitHub issue ID to process (to):</question> ', false);
				$this->rangeTo = (int) trim($this->getApplication()->in());
			}
		}

		return $this;
	}

	/**
	 * Check that an issue number is within a given range.
	 *
	 * @param   integer  $number  The number.
	 *
	 * @return boolean
	 *
	 * @since   1.0
	 */
	protected function checkInRange($number)
	{
		if (!$this->rangeFrom)
		{
			return true;
		}

		return ($number >= $this->rangeFrom && $number <= $this->rangeTo)
			? true : false;
	}
}
