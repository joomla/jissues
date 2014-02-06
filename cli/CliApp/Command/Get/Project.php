<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace CliApp\Command\Get;

use CliApp\Command\Get\Project\Comments;
use CliApp\Command\Get\Project\Events;
use CliApp\Command\Get\Project\Issues;
use CliApp\Command\Get\Project\Labels;
use CliApp\Command\Get\Project\Milestones;
use CliApp\Command\TrackerCommandOption;

/**
 * Class for retrieving issues from GitHub for selected projects
 *
 * @since  1.0
 */
class Project extends Get
{
	/**
	 * The command "description" used for help texts.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $description = 'Get the whole project info from GitHub, including issues and issue comments.';

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

	protected $changedIssueNumbers = array();

	protected $force = false;

	/**
	 * Constructor.
	 *
	 * @since   1.0
	 */
	public function __construct()
	{
		parent::__construct();

		$this
			->addOption(
				new TrackerCommandOption(
					'all', '',
					'Process all issues.'
				)
			)
			->addOption(
				new TrackerCommandOption(
					'issue', '',
					'<n> Process only a single issue.'
				)
			)
			->addOption(
				new TrackerCommandOption(
					'range_from', '',
					'<n> First issue to process.'
				)
			)
			->addOption(
				new TrackerCommandOption(
					'range_to', '',
					'<n> Last issue to process.'
				)
			)
			->addOption(
				new TrackerCommandOption(
					'force', 'f',
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
				sprintf(
					'Updating project info for project: %s/%s',
					$this->project->gh_user,
					$this->project->gh_project
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
		$this->force = $this->getApplication()->input->get('force', $this->getApplication()->input->get('f'));

		$this->usePBar = $this->getApplication()->get('cli-application.progress-bar');

		if ($this->getApplication()->input->get('noprogress'))
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
	 * Process the project labels.
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
		$issues->rangeTo = $this->rangeTo;
		$issues->force = $this->force;
		$issues->usePBar = $this->usePBar;

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
		$comments->force = $this->force;

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
		$issue = $this->getApplication()->input->getInt('issue');

		$rangeFrom = $this->getApplication()->input->getInt('range_from');
		$rangeTo = $this->getApplication()->input->getInt('range_to');

		if ($this->getApplication()->input->get('all'))
		{
			// Process all issues - do nothing
		}
		elseif ($issue)
		{
			// Process only a single issue
			$this->rangeFrom = $issue;
			$this->rangeTo = $issue;
		}
		elseif ($rangeFrom && $rangeTo)
		{
			// Process a range of issues
			$this->rangeFrom = $rangeFrom;
			$this->rangeTo = $rangeTo;
		}
		else
		{
			// Select what to process
			$this->out('<question>GitHub issues to process?</question> <b>[a]ll</b> / [r]ange :', false);

			$resp = trim($this->getApplication()->in());

			if ($resp == 'r' || $resp == 'range')
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
