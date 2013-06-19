<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CliApp\Application;

use Elkuku\Console\Helper\ConsoleProgressBar;
use Joomla\Application\AbstractCliApplication;
use Joomla\Application\Cli\ColorProcessor;
use Joomla\Application\Cli\ColorStyle;
use Joomla\Github\Github;
use Joomla\Input;
use Joomla\Registry\Registry;
use Joomla\Database\DatabaseDriver;

use CliApp\Command\TrackerCommandOption;
use CliApp\Exception\AbortException;
use JTracker\Authentication\GitHub\GitHubUser;

/**
 * CLI application for installing the tracker application
 *
 * @since  1.0
 */
class TrackerApplication extends AbstractCliApplication
{
	/**
	 * Database object
	 *
	 * @var    DatabaseDriver
	 * @since  1.0
	 */
	private $database = null;

	/**
	 * @var Github
	 */
	private $gitHub = null;

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
			'Supprees ANSI colors on unsupported terminals.'
		);

		$this->loadConfiguration();

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
	 * Get a database driver object.
	 *
	 * @return  DatabaseDriver
	 *
	 * @since   1.0
	 */
	public function getDatabase()
	{
		if (is_null($this->database))
		{
			return $this->createDatabase();
		}

		return $this->database;
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
	 * @return  TrackerApplication
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
	 * @return  TrackerApplication
	 */
	public function debugOut($text)
	{
		return ($this->verbose) ? $this->out('DEBUG ' . $text) : $this;
	}

	/**
	 * Output a nicely formatted title for the application.
	 *
	 * @param   string   $title     The title to display.
	 * @param   string   $subTitle  A subtitle
	 * @param   integer  $width     Total width in chars
	 *
	 * @return  TrackerApplication
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
	 * Load the application configuration.
	 *
	 * @return  TrackerApplication
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	protected function loadConfiguration()
	{
		// Set the configuration file path for the application.
		$file = realpath(__DIR__ . '/../../..') . '/etc/config.json';

		// Verify the configuration exists and is readable.
		if (!is_readable($file))
		{
			throw new \RuntimeException('Configuration file does not exist or is unreadable.');
		}

		// Load the configuration file into an object.
		$config = json_decode(file_get_contents($file));

		if ($config === null)
		{
			throw new \RuntimeException(sprintf('Unable to parse the configuration file %s.', $file));
		}

		$this->config->loadObject($config);

		return $this;
	}

	/**
	 * Create an database object.
	 *
	 * @return  DatabaseDriver  Database driver instance
	 *
	 * @see     DatabaseDriver::getInstance()
	 * @since   1.0
	 */
	protected function createDatabase()
	{
		$options = array(
			'driver' => $this->get('database.driver'),
			'host' => $this->get('database.host'),
			'user' => $this->get('database.user'),
			'password' => $this->get('database.password'),
			'database' => $this->get('database.name'),
			'prefix' => $this->get('database.prefix')
		);

		$database = DatabaseDriver::getInstance($options);

		$database->setDebug($this->get('debug'));

		$this->database = $database;

		return $database;
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
	 * Get a GitHub object.
	 *
	 * @since  1.0
	 * @throws \RuntimeException
	 * @return Github
	 */
	public function getGitHub()
	{
		if (is_null($this->gitHub))
		{
			$options = new Registry;

			if ($this->input->get('auth'))
			{
				$resp = 'yes';
			}
			else
			{
				// Ask if the user wishes to authenticate to GitHub.  Advantage is increased rate limit to the API.
				$this->out('<question>Do you wish to authenticate to GitHub?</question> [y]es / <b>[n]o</b> :', false);

				$resp = trim($this->in());
			}

			if ($resp == 'y' || $resp == 'yes')
			{
				// Set the options
				$options->set('api.username', $this->get('github.username', ''));
				$options->set('api.password', $this->get('github.password', ''));

				$this->debugOut('GitHub credentials: ' . print_r($options, true));
			}

			// @todo temporary fix to avoid the "Socket" transport protocol
			$transport = \Joomla\Http\HttpFactory::getAvailableDriver($options, array('curl'));

			if (false == is_a($transport, 'Joomla\\Http\\Transport\\Curl'))
			{
				throw new \RuntimeException('Please enable cURL.');
			}

			$http = new \Joomla\Github\Http($options, $transport);

			$this->debugOut(get_class($transport));

			// Instantiate Github
			$this->gitHub = new Github($options, $http);

			// @todo after fix this should be enough:
			// $this->github = new Github($options);
		}

		return $this->gitHub;
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

		$rate = $this->getGitHub()->authorization->getRateLimit()->rate;

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
