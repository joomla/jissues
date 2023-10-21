<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Application\Command;

use App\Projects\TrackerProject;

use Application\Exception\AbortException;

use Joomla\Console\Command\AbstractCommand;
use Joomla\DI\ContainerAwareInterface;
use Joomla\DI\ContainerAwareTrait;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * TrackerCommand class
 *
 * @since  1.0
 */
abstract class TrackerCommand extends AbstractCommand implements LoggerAwareInterface, ContainerAwareInterface
{
	use LoggerAwareTrait;
	use ContainerAwareTrait;

	/**
	 * Use the progress bar.
	 *
	 * @var    boolean
	 * @since  1.0
	 */
	protected $usePBar;

	/**
	 * The project object.
	 *
	 * @var    TrackerProject
	 * @since  1.0
	 */
	protected $project;

	/**
	 * Write a string to standard output.
	 *
	 * @param   string   $text  The text to display.
	 * @param   boolean  $nl    True (default) to append a new line at the end of the output string.
	 *
	 * @return  $this
	 *
	 * @codeCoverageIgnore
	 * @since   1.0
	 */
	protected function out($text = '', $nl = true)
	{
		$this->getApplication()->out($text, $nl);

		return $this;
	}

	/**
	 * Write a string to standard output in "verbose" mode.
	 *
	 * @param   string  $text  The text to display.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	protected function debugOut($text)
	{
		$this->getApplication()->debugOut($text);

		return $this;
	}

	/**
	 * Pass a string to the attached logger.
	 *
	 * @param   string  $text  The text to display.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	protected function logOut($text)
	{
		// Send text to the logger and remove color chars.
		$this->getLogger()->info(preg_replace('/\<[a-z\/]+\>/', '', $text));

		return $this;
	}

	/**
	 * Get the logger object.
	 *
	 * @return  \Psr\Log\LoggerInterface
	 *
	 * @since   1.0
	 */
	protected function getLogger()
	{
		return $this->getContainer()->get('logger');
	}

	/**
	 * Display the GitHub rate limit.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	protected function displayGitHubRateLimit()
	{
		$this->getApplication()->displayGitHubRateLimit();

		return $this;
	}

	/**
	 * Get a progress bar object.
	 *
	 * @param   integer  $targetNum  The target number.
	 *
	 * @return  \Elkuku\Console\Helper\ConsoleProgressBar
	 *
	 * @since   1.0
	 */
	protected function getProgressBar($targetNum)
	{
		return $this->getApplication()->getProgressBar($targetNum);
	}

	/**
	 * Select the project.
	 *
	 * @param   InputInterface  $input  The input to inject into the command.
	 * @param   SymfonyStyle    $io     The output object for rendering text.
	 *
	 * @return  $this
	 *
	 * @throws  \RuntimeException
	 * @throws  AbortException
	 * @since   1.0
	 */
	protected function selectProject(InputInterface $input, SymfonyStyle $io)
	{
		/** @var \Joomla\Database\DatabaseDriver $db */
		$db = $this->getContainer()->get('db');

		$projects = $db->setQuery(
			$db->getQuery(true)
				->from($db->quoteName('#__tracker_projects'))
				->select(['project_id', 'title', 'gh_user', 'gh_project', 'gh_editbot_user', 'gh_editbot_pass'])
		)->loadObjectList();

		$id = (integer) $input->getOption('project');

		if (!$id)
		{
			$io->newLine();
			$io->text('<b>Available projects:</b>');
			$io->newLine();

			$cnt = 1;

			$checks = [];

			foreach ($projects as $project)
			{
				if ($project->gh_user && $project->gh_project)
				{
					$io->text('  <b>' . $cnt . '</b> (id: ' . $project->project_id . ') ' . $project->title);
					$checks[$cnt] = $project;
					$cnt++;
				}
			}

			$io->newLine();
			$question = new ChoiceQuestion('Select a project:');
			$resp = (int) $io->askQuestion($question);

			if (!$resp)
			{
				throw new AbortException('Aborted');
			}

			if (\array_key_exists($resp, $checks) === false)
			{
				throw new AbortException('Invalid project');
			}

			$this->project = new TrackerProject($db, $checks[$resp]);
		}
		else
		{
			foreach ($projects as $project)
			{
				if ($project->project_id == $id)
				{
					$this->project = new TrackerProject($db, $project);

					break;
				}
			}

			if ($this->project === null)
			{
				throw new AbortException('Invalid project');
			}
		}

		$this->logOut(sprintf('Processing project: <info>%s</info>', $this->project->title));

		// TODO: FIX ME!!
		$this->getApplication()->getInput()->set('project', $this->project->project_id);

		return $this;
	}

	/**
	 * Execute a command on the server.
	 *
	 * @param   string  $command  The command to execute.
	 *
	 * @return string
	 *
	 * @since   1.0
	 * @throws \RuntimeException
	 */
	protected function execCommand($command)
	{
		$lastLine = system($command, $status);

		if ($status)
		{
			// Command exited with a status != 0
			if ($lastLine)
			{
				$this->logOut($lastLine);

				throw new \RuntimeException($lastLine);
			}

			$this->logOut('An unknown error occurred');

			throw new \RuntimeException('An unknown error occurred');
		}

		return $lastLine;
	}
}
