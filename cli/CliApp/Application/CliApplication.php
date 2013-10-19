<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CliApp\Application;

use CliApp\Command\TrackerCommandOption;
use CliApp\Exception\AbortException;
use CliApp\Service\GitHubProvider;
use CliApp\Service\ApplicationProvider;
use CliApp\Service\LoggerProvider;

use Elkuku\Console\Helper\ConsoleProgressBar;

use Joomla\Application\AbstractCliApplication;
use Joomla\Application\Cli\ColorProcessor;
use Joomla\Application\Cli\ColorStyle;
use Joomla\Input;
use Joomla\Registry\Registry;

use JTracker\Authentication\GitHub\GitHubUser;
use JTracker\Container;
use JTracker\Service\ConfigurationProvider;
use JTracker\Service\DatabaseProvider;
use JTracker\Service\DebuggerProvider;

/**
 * CLI application for installing the tracker application
 *
 * @since  1.0
 */
class CliApplication extends AbstractCliApplication
{
	/**
	 * Quiet mode - no output.
	 *
	 * @var    boolean
	 * @since  1.0
	 */
	private $quiet = false;

	/**
	 * Verbose mode - debug output.
	 *
	 * @var    bool
	 * @since  1.0
	 */
	private $verbose = false;

	/**
	 * Use the progress bar.
	 *
	 * @var    boolean
	 * @since  1.0
	 */
	protected $usePBar;

	/**
	 * Progress bar format.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $pBarFormat = '[%bar%] %fraction% %elapsed% ETA: %estimate%';

	/**
	 * Array of TrackerCommandOption objects
	 *
	 * @var    array
	 * @since  1.0
	 */
	protected $commandOptions = array();

	/**
	 * Class constructor.
	 *
	 * @param   Input\Cli  $input   An optional argument to provide dependency injection for the application's
	 *                              input object.  If the argument is a InputCli object that object will become
	 *                              the application's input object, otherwise a default input object is created.
	 * @param   Registry   $config  An optional argument to provide dependency injection for the application's
	 *                              config object.  If the argument is a Registry object that object will become
	 *                              the application's config object, otherwise a default config object is created.
	 *
	 * @since   1.0
	 */
	public function __construct(Input\Cli $input = null, Registry $config = null)
	{
		parent::__construct($input, $config);

		// Build the DI Container
		Container::getInstance()
			->registerServiceProvider(new ApplicationProvider($this))
			->registerServiceProvider(new ConfigurationProvider($this->config))
			->registerServiceProvider(new DatabaseProvider)
			->registerServiceProvider(new GitHubProvider)
			->registerServiceProvider(new DebuggerProvider)
			->registerServiceProvider(new LoggerProvider($this->input->get('log'), $this->input->get('quiet', $this->input->get('q'))));

		$this->commandOptions[] = new TrackerCommandOption(
			'quiet', 'q',
			'Be quiet - suppress output.'
		);

		$this->commandOptions[] = new TrackerCommandOption(
			'verbose', 'v',
			'Verbose output for debugging purpose.'
		);

		$this->commandOptions[] = new TrackerCommandOption(
			'nocolors', '',
			'Suppress ANSI colors on unsupported terminals.'
		);

		/* @type ColorProcessor $processor */
		$processor = $this->getOutput()->getProcessor();

		if ($this->input->get('nocolors') || !$this->get('cli-application.colors'))
		{
			$processor->noColors = true;
		}

		// Setup app colors (also required in "nocolors" mode - to strip them).
		$processor
			->addStyle('b', new ColorStyle('', '', array('bold')))
			->addStyle('title', new ColorStyle('yellow', '', array('bold')))
			->addStyle('ok', new ColorStyle('green', '', array('bold')));

		$this->usePBar = $this->get('cli-application.progress-bar');

		if ($this->input->get('noprogress'))
		{
			$this->usePBar = false;
		}
	}

	/**
	 * Method to run the application routines.  Most likely you will want to instantiate a controller
	 * and execute it, or perform some sort of task directly.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	protected function doExecute()
	{
		$this->quiet   = $this->input->get('quiet', $this->input->get('q'));
		$this->verbose = $this->input->get('verbose', $this->input->get('v'));

		$this->outputTitle('Joomla! Tracker CLI Application', '1.1');

		$args = $this->input->args;

		if (!$args || (isset($args[0]) && 'help' == $args[0]))
		{
			$command = 'help';
			$action  = 'help';
		}
		else
		{
			$command = $args[0];

			$action = (isset($args[1])) ? $args[1] : $command;
		}

		if ('retrieve' == $command)
		{
			// @legacy JTracker
			$command = 'get';
		}

		$className = 'CliApp\\Command\\' . ucfirst($command) . '\\' . ucfirst($action);

		if (false == class_exists($className))
		{
			$this->out()
				->out('<error>Invalid command</error>: ' . (($command == $action) ? $command : $command . ' ' . $action))
				->out();

			$className = 'CliApp\\Command\\Help\\Help';
		}

		if (false == method_exists($className, 'execute'))
		{
			throw new \RuntimeException(sprintf('Missing method %1$s::%2$s', $className, 'execute'));
		}

		try
		{
			with(new $className($this))->execute();
		}
		catch (AbortException $e)
		{
			$this->out('')
				->out('<comment>Process aborted.</comment>');
		}

		$this->out()
			->out(str_repeat('_', 40))
			->out(
				sprintf(
					'Execution time: <b>%d sec.</b>',
					time() - $this->get('execution.timestamp')
				)
			)
			->out(str_repeat('_', 40));
	}

	/**
	 * Write a string to standard output.
	 *
	 * @param   string   $text     The text to display.
	 * @param   boolean  $newline  True (default) to append a new line at the end of the output string.
	 *
	 * @return  CliApplication
	 *
	 * @codeCoverageIgnore
	 * @since   1.0
	 */
	public function out($text = '', $newline = true)
	{
		return ($this->quiet) ? $this : parent::out($text, $newline);
	}

	/**
	 * Write a string to standard output in "verbose" mode.
	 *
	 * @param   string  $text  The text to display.
	 *
	 * @since   1.0
	 * @return  CliApplication
	 */
	public function debugOut($text)
	{
		return ($this->verbose) ? $this->out('DEBUG ' . $text) : $this;
	}

	/**
	 * Output a nicely formatted title for the application.
	 *
	 * @param   string   $title     The title to display.
	 * @param   string   $subTitle  A subtitle.
	 * @param   integer  $width     Total width in chars.
	 *
	 * @return  CliApplication
	 *
	 * @since   1.0
	 */
	public function outputTitle($title, $subTitle = '', $width = 60)
	{
		$this->out(str_repeat('-', $width));

		$this->out(str_repeat(' ', $width / 2 - (strlen($title) / 2)) . '<title>' . $title . '</title>');

		if ($subTitle)
		{
			$this->out(str_repeat(' ', $width / 2 - (strlen($subTitle) / 2)) . '<b>' . $subTitle . '</b>');
		}

		$this->out(str_repeat('-', $width));

		return $this;
	}

	/**
	 * Get the command options.
	 *
	 * @return  array
	 *
	 * @since   1.0
	 */
	public function getCommandOptions()
	{
		return $this->commandOptions;
	}

	/**
	 * This is a useless legacy function.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 * @todo    Remove
	 */
	public function getUserStateFromRequest()
	{
		return '';
	}

	/**
	 * Get a user object.
	 *
	 * Some methods check for an authenticated user...
	 *
	 * @since  1.0
	 * @return GitHubUser
	 */
	public function getUser()
	{
		// Urgh..
		$user = new GitHubUser;
		$user->isAdmin = true;

		return $user;
	}

	/**
	 * Display the GitHub rate limit.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	public function displayGitHubRateLimit()
	{
		$this->out()
			->out('<info>GitHub rate limit:...</info> ', false);

		$rate = Container::retrieve('gitHub')->authorization->getRateLimit()->rate;

		$this->out(sprintf('%1$d (remaining: <b>%2$d</b>)', $rate->limit, $rate->remaining))
			->out();

		return $this;
	}

	/**
	 * Get a progress bar object.
	 *
	 * @param   integer  $targetNum  The target number.
	 *
	 * @return  ConsoleProgressBar
	 *
	 * @since   1.0
	 */
	public function getProgressBar($targetNum)
	{
		return ($this->usePBar)
			? new ConsoleProgressBar($this->pBarFormat, '=>', ' ', 60, $targetNum)
			: null;
	}
}
