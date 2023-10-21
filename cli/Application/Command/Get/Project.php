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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class for retrieving issues from GitHub for selected projects
 *
 * @since  1.0
 */
class Project extends Get
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
	 * Configure the command.
	 *
	 * @return  void
	 *
	 * @since   2.0.0
	 */
	protected function configure(): void
	{
		$this->setName('get:project');
		$this->setDescription('Get the whole project info from GitHub, including issues and issue comments.');

		$this->addOption('all', '', InputOption::VALUE_OPTIONAL, 'Process all issues.');
		$this->addOption('issue', '', InputOption::VALUE_OPTIONAL, 'Process only a single issue.');
		$this->addOption('range_from', '', InputOption::VALUE_OPTIONAL, 'First issue to process.');
		$this->addOption('range_to', '', InputOption::VALUE_OPTIONAL, 'Last issue to process.');
		$this->addOption('force', 'f', InputOption::VALUE_OPTIONAL, 'Force an update even if the issue has not changed.');
	}

	/**
	 * Execute the command.
	 *
	 * @param   InputInterface   $input   The input to inject into the command.
	 * @param   OutputInterface  $output  The output to inject into the command.
	 *
	 * @return  integer
	 *
	 * @since   1.0
	 */
	protected function doExecute(InputInterface $input, OutputInterface $output): int
	{
		$ioStyle = new SymfonyStyle($input, $output);
		$ioStyle->title('Retrieve Project');

		$this->logOut('---- Bulk Start retrieve Project');

		$this
			->selectProject($input, $ioStyle)
			->setParams($input)
			->selectRange($input, $ioStyle)
			->setupGitHub()
			->displayGitHubRateLimit($ioStyle);
		$ioStyle->text(
			strtr(
				'Updating project info for project: %user%/%project%',
				['%user%' => $this->project->gh_user, '%project%' => $this->project->gh_project]
			)
		);
		$this->processLabels()
			->processMilestones()
			->processIssues()
			->processComments()
			->processEvents()
			->processAvatars();
		$ioStyle->newLine();
		$this->logOut('---- Bulk Finished');

		return Command::SUCCESS;
	}

	/**
	 * Set internal parameters from the input..
	 *
	 * @param   InputInterface  $input  The input to inject into the command.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	protected function setParams(InputInterface $input)
	{
		$this->force = $input->getOption('force');

		$this->usePBar = $this->getApplication()->get('cli-application.progress-bar');

		if ($input->getOption('noprogress'))
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
	 * @param   InputInterface  $input  The input to inject into the command.
	 * @param   SymfonyStyle    $io     Add output interface
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	protected function selectRange(InputInterface $input, SymfonyStyle $io)
	{
		$issue = (integer) $input->getOption('issue');

		$rangeFrom = (integer) $input->getOption('range_from');
		$rangeTo   = (integer) $input->getOption('range_to');

		if ($input->getOption('all'))
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
			$question = new ChoiceQuestion(
				'GitHub issues to process? 1) All, 2) Range',
				['1', '2']
			);

			$resp = $io->askQuestion($question);

			if ((int) $resp == 2)
			{
				// Get the first GitHub issue (from)
				$question = new Question(
					'Enter the first GitHub issue ID to process (from):',
				);
				$this->rangeFrom = (int) $io->askQuestion($question);

				// Get the ending GitHub issue (to)
				$question = new Question(
					'Enter the latest GitHub issue ID to process (to):',
				);
				$this->rangeTo = (int) $io->askQuestion($question);
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
