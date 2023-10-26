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

		$this->addProgressBarOption();
		$this->addProjectOption();
		$this->addOption('all', '', InputOption::VALUE_NONE, 'Process all issues.');
		$this->addOption('issue', '', InputOption::VALUE_REQUIRED, 'Process only a single issue.');
		$this->addOption('range_from', '', InputOption::VALUE_REQUIRED, 'First issue to process.');
		$this->addOption('range_to', '', InputOption::VALUE_REQUIRED, 'Last issue to process.');
		$this->addOption('force', 'f', InputOption::VALUE_NONE, 'Force an update even if the issue has not changed.');
		$this->addStatusOption('all');
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
		$this->processLabels($input, $output)
			->processMilestones($input, $output)
			->processIssues($input, $output)
			->processComments($input, $output)
			->processEvents($input, $output)
			->processAvatars($input, $output);
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
	 * @param   InputInterface   $input   The input to inject into the command.
	 * @param   OutputInterface  $output  The output to inject into the command.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	protected function processLabels(InputInterface $input, OutputInterface $output)
	{
		$labelsCommand = $this->getApplication()->getCommand(Labels::COMMAND_NAME);
		$labelsCommand->execute($input, $output);

		return $this;
	}

	/**
	 * Process the project milestones.
	 *
	 * @param   InputInterface   $input   The input to inject into the command.
	 * @param   OutputInterface  $output  The output to inject into the command.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	protected function processMilestones(InputInterface $input, OutputInterface $output)
	{
		$labelsCommand = $this->getApplication()->getCommand(Milestones::COMMAND_NAME);
		$labelsCommand->execute($input, $output);

		return $this;
	}

	/**
	 * Process the project issues.
	 *
	 * @param   InputInterface   $input   The input to inject into the command.
	 * @param   OutputInterface  $output  The output to inject into the command.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	protected function processIssues(InputInterface $input, OutputInterface $output)
	{
		/** @var Issues $issues */
		$issues = $this->getApplication()->getCommand(Issues::COMMAND_NAME);

		$issues->rangeFrom = $this->rangeFrom;
		$issues->rangeTo   = $this->rangeTo;
		$issues->force     = $this->force;
		$issues->usePBar   = $this->usePBar;

		$issues->execute($input, $output);

		$this->changedIssueNumbers = $issues->getChangedIssueNumbers();

		return $this;
	}

	/**
	 * Process the project comments.
	 *
	 * @param   InputInterface   $input   The input to inject into the command.
	 * @param   OutputInterface  $output  The output to inject into the command.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	protected function processComments(InputInterface $input, OutputInterface $output)
	{
		/** @var Comments $comments */
		$comments = $this->getApplication()->getCommand(Comments::COMMAND_NAME);

		$comments->usePBar = $this->usePBar;
		$comments->force   = $this->force;

		$comments->setChangedIssueNumbers($this->changedIssueNumbers)
			->execute($input, $output);

		return $this;
	}

	/**
	 * Process the project events.
	 *
	 * @param   InputInterface   $input   The input to inject into the command.
	 * @param   OutputInterface  $output  The output to inject into the command.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	protected function processEvents(InputInterface $input, OutputInterface $output)
	{
		/** @var Events $comments */
		$events = $this->getApplication()->getCommand(Events::COMMAND_NAME);

		$events->usePBar = $this->usePBar;

		$events->setChangedIssueNumbers($this->changedIssueNumbers)
			->execute($input, $output);

		return $this;
	}

	/**
	 * Process the project avatars.
	 *
	 * @param   InputInterface   $input   The input to inject into the command.
	 * @param   OutputInterface  $output  The output to inject into the command.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	protected function processAvatars(InputInterface $input, OutputInterface $output)
	{
		/** @var Avatars $comments */
		$avatars = $this->getApplication()->getCommand(Avatars::COMMAND_NAME);
		$avatars->execute($input, $output);

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

	/**
	 * Common Option for status filtering.
	 *
	 * @param   mixed  $default  The default value for the status field
	 *
	 * @return  void
	 *
	 * @since   2.0.0
	 */
	protected function addStatusOption($default = null): void
	{
		$this->addOption('status', '', InputOption::VALUE_REQUIRED, 'Process only an issue of given status.', $default);
	}
}
