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

use Joomla\DI\ContainerAwareInterface;
use Joomla\DI\ContainerAwareTrait;

use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * TrackerCommand class
 *
 * @since  1.0
 */
abstract class TrackerCommand implements LoggerAwareInterface, ContainerAwareInterface
{
	use LoggerAwareTrait;
	use ContainerAwareTrait;

	/**
	 * Array of options.
	 *
	 * @var    TrackerCommandOption[]
	 * @since  1.0
	 */
	protected $options = [];

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
	 * The project object.
	 *
	 * @var    TrackerProject
	 * @since  1.0
	 */
	protected $project;

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
		// Check if the option has been defined already.
		foreach ($this->options as $hasOption)
		{
			if ($hasOption->longArg == $option->longArg)
			{
				throw new \UnexpectedValueException(
					sprintf('The command "%s" already has an option "%s"', get_class($this), $option->longArg)
				);
			}

			if ($hasOption->shortArg && $hasOption->shortArg == $option->shortArg)
			{
				throw new \UnexpectedValueException(
					sprintf('The command "%s" already has an option "%s"', get_class($this), $option->shortArg)
				);
			}
		}

		$this->options[] = $option;

		return $this;
	}

	/**
	 * Get the current value of a command option.
	 *
	 * Checks first the long option (e.g. --option) then the short option (e.g. -o).
	 *
	 * @param   string  $name  the option name.
	 *
	 * @return string
	 */
	protected function getOption($name)
	{
		$input = $this->getApplication()->input;

		foreach ($this->options as $option)
		{
			if ($option->longArg == $name)
			{
				return $option->shortArg
				? $input->get($option->longArg, $input->get($option->shortArg))
				: $input->get($option->longArg);
			}
		}

		throw new \UnexpectedValueException(sprintf('Option "%s" has not been added to class "%s"', $name, get_class($this)));
	}

	/**
	 * Get defined options.
	 *
	 * @return TrackerCommandOption[]
	 */
	public function getOptions()
	{
		return $this->options;
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
	 * Display an error "page" if no options have been found for a given command.
	 *
	 * @param   string  $dir  The base directory for the commands.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	public function displayMissingOption($dir)
	{
		$command = strtolower(implode('', array_slice(explode('\\', get_class($this)), -1)));

		$this->getApplication()->outputTitle(sprintf(g11n3t('Command: %s'), ucfirst($command)));

		$errorTitle1 = sprintf(g11n3t('Missing option for command: %s'), $command);
		$errorTitle2 = g11n3t('Please use one of the following :');

		$maxLen = (strlen($errorTitle1) > strlen($errorTitle2)) ? strlen($errorTitle1) : strlen($errorTitle2);

		$filesystem = new Filesystem(new Local($dir));

		$this->out('<error>  ' . str_repeat(' ', $maxLen) . '  </error>');
		$this->out('<error>  ' . $errorTitle1 . str_repeat(' ', $maxLen - strlen($errorTitle1)) . '  </error>');
		$this->out('<error>  ' . $errorTitle2 . str_repeat(' ', $maxLen - strlen($errorTitle2)) . '  </error>');
		$this->out('<error>  ' . str_repeat(' ', $maxLen) . '  </error>');

		$files = $filesystem->listContents();
		sort($files);

		foreach ($files as $file)
		{
			$cmd = strtolower($file['filename']);

			if ('file' != $file['type'] || $command == $cmd)
			{
				// Exclude the base class
				continue;
			}

			$this->out('<error>  ' . $command . ' ' . $cmd
				. str_repeat(' ', $maxLen - strlen($cmd) - strlen($command) + 1)
				. '</error>'
			);
		}

		$this->out('<error>  ' . str_repeat(' ', $maxLen) . '  </error>');

		return $this;
	}

	/**
	 * Get the application object.
	 *
	 * @return  \Application\Application
	 *
	 * @since   1.0
	 */
	protected function getApplication()
	{
		return $this->getContainer()->get('app');
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
	 * @return  $this
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 * @throws  AbortException
	 */
	protected function selectProject()
	{
		/** @var \Joomla\Database\DatabaseDriver $db */
		$db = $this->getContainer()->get('db');

		$projects = $db->setQuery(
			$db->getQuery(true)
				->from($db->quoteName('#__tracker_projects'))
				->select(['project_id', 'title', 'gh_user', 'gh_project', 'gh_editbot_user', 'gh_editbot_pass'])
		)->loadObjectList();

		$id = (integer) $this->getOption('project');

		if (!$id)
		{
			$this->out()
				->out('<b>' . g11n3t('Available projects:') . '</b>')
				->out();

			$cnt = 1;

			$checks = [];

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
				->out('<question>' . g11n3t('Select a project:') . '</question> ', false);

			$resp = (int) trim($this->getApplication()->in());

			if (!$resp)
			{
				throw new AbortException(g11n3t('Aborted'));
			}

			if (false === array_key_exists($resp, $checks))
			{
				throw new AbortException(g11n3t('Invalid project'));
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

			if (is_null($this->project))
			{
				throw new AbortException(g11n3t('Invalid project'));
			}
		}

		$this->logOut(sprintf(g11n3t('Processing project: %s'), '<info>' . $this->project->title . '</info>'));

		$this->getApplication()->input->set('project', $this->project->project_id);

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

			$this->logOut(g11n3t('An unknown error occurred'));

			throw new \RuntimeException(g11n3t('An unknown error occurred'));
		}

		return $lastLine;
	}
}
