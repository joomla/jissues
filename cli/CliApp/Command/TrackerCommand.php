<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CliApp\Command;

use App\Projects\Model\ProjectsModel;

use CliApp\Exception\AbortException;

use JTracker\Container;

use Monolog\Logger;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

/**
 * TrackerCommand class
 *
 * @since  1.0
 */
abstract class TrackerCommand implements LoggerAwareInterface
{
	/**
	 * Application object.
	 *
	 * @var    \CliApp\Application\CliApplication
	 * @since  1.0
	 */
	protected $application;

	/**
	 * Logger object.
	 *
	 * @var    Logger
	 * @since  1.0
	 */
	protected $logger;

	/**
	 * Array of options.
	 *
	 * @var    array
	 * @since  1.0
	 */
	protected $options = array();

	/**
	 * The command "description" used for help texts.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $description = '';

	/**
	 * Use the progress bar.
	 *
	 * @var    boolean
	 * @since  1.0
	 */
	protected $usePBar;

	/**
	 * Constructor.
	 *
	 * @since   1.0
	 */
	public function __construct()
	{
		$this->application = Container::retrieve('app');
		$this->logger      = Container::retrieve('logger');
		$this->usePBar     = $this->application->get('cli-application.progress-bar');

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
	abstract public function execute();

	/**
	 * Get a description text.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	public function getDescription()
	{
		return $this->description;
	}

	/**
	 * Add a command option.
	 *
	 * @param   TrackerCommandOption  $option  The command option.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	protected function addOption(TrackerCommandOption $option)
	{
		$this->options[] = $option;

		return $this;
	}

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
		$this->application->out($text, $nl);

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
		$this->application->debugOut($text);

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
		$this->logger->info(preg_replace('/\<[a-z\/]+\>/', '', $text));

		return $this;
	}

	/**
	 * Write a string to the standard output if an operation has terminated successfully.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	protected function outOK()
	{
		return $this->out('<ok>ok</ok>');
	}

	/**
	 * Sets a logger instance on the object
	 *
	 * @param   LoggerInterface  $logger  The logger interface
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function setLogger(LoggerInterface $logger)
	{
		$this->logger = $logger;
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
		$this->application->displayGitHubRateLimit();

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
		return $this->application->getProgressBar($targetNum);
	}

	/**
	 * Select the project.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 * @throws  AbortException
	 */
	protected function selectProject()
	{
		$projects = with(new ProjectsModel(Container::getInstance()->get('db')))->getItems();

		$id = $this->application->input->getInt('project', $this->application->input->getInt('p'));

		if (!$id)
		{
			$this->out()
				->out('<b>Available projects:</b>')
				->out();

			$cnt = 1;

			$checks = array();

			foreach ($projects as $project)
			{
				if ($project->gh_user && $project->gh_project)
				{
					$this->out('  <b>' . $cnt . '</b> (id: ' . $project->project_id . ') ' . $project->title);
					$checks[$cnt] = $project;
					$cnt++;
				}
			}

			$this->out()
				->out('<question>Select a project:</question> ', false);

			$resp = (int) trim($this->application->in());

			if (!$resp)
			{
				throw new AbortException('Aborted');
			}

			if (false == array_key_exists($resp, $checks))
			{
				throw new AbortException('Invalid project');
			}

			$this->project = $checks[$resp];
		}
		else
		{
			foreach ($projects as $project)
			{
				if ($project->project_id == $id)
				{
					$this->project = $project;

					break;
				}
			}

			if (is_null($this->project))
			{
				throw new AbortException('Invalid project');
			}
		}

		$this->logOut('Processing project: <info>' . $this->project->title . '</info>');

		return $this;
	}
}
