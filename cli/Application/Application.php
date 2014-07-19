<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Application;

use Application\Command\Help\Help;
use App\Projects\TrackerProject;

use Application\Command\TrackerCommand;
use Application\Command\TrackerCommandOption;
use Application\Exception\AbortException;
use Application\Service\LoggerProvider;

use Elkuku\Console\Helper\ConsoleProgressBar;

use g11n\g11n;

use Joomla\Application\AbstractCliApplication;
use Joomla\Application\Cli\Output\Processor\ColorProcessor;
use Joomla\Application\Cli\ColorStyle;
use Joomla\DI\Container;
use Joomla\DI\ContainerAwareInterface;
use Joomla\DI\ContainerAwareTrait;
use Joomla\Event\Dispatcher;
use Joomla\Event\DispatcherAwareInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\Input;
use Joomla\Registry\Registry;

use JTracker\Authentication\GitHub\GitHubUser;
use JTracker\Service\ApplicationProvider;
use JTracker\Service\ConfigurationProvider;
use JTracker\Service\DatabaseProvider;
use JTracker\Service\DebuggerProvider;
use JTracker\Service\GitHubProvider;
use JTracker\Service\TransifexProvider;

/**
 * CLI application for installing the tracker application
 *
 * @since  1.0
 */
class Application extends AbstractCliApplication implements DispatcherAwareInterface
{
	use ContainerAwareTrait;

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
	 * @var    boolean
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
	 * Event Dispatcher
	 *
	 * @var    Dispatcher
	 * @since  1.0
	 */
	private $dispatcher;

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
		$this->container = (new Container)
			->registerServiceProvider(new ApplicationProvider($this))
			->registerServiceProvider(new ConfigurationProvider($this->config))
			->registerServiceProvider(new DatabaseProvider)
			->registerServiceProvider(new GitHubProvider)
			->registerServiceProvider(new DebuggerProvider)
			->registerServiceProvider(new LoggerProvider($this->input->get('log'), $this->input->get('quiet', $this->input->get('q'))))
			->registerServiceProvider(new TransifexProvider);

		$this->loadLanguage();

		$this->commandOptions[] = new TrackerCommandOption(
			'quiet', 'q',
			g11n3t('Be quiet - suppress output.')
		);

		$this->commandOptions[] = new TrackerCommandOption(
			'verbose', 'v',
			g11n3t('Verbose output for debugging purpose.')
		);

		$this->commandOptions[] = new TrackerCommandOption(
			'nocolors', '',
			g11n3t('Suppress ANSI colors on unsupported terminals.')
		);

		$this->commandOptions[] = new TrackerCommandOption(
			'--log=filename.log', '',
			g11n3t('Optionally log output to the specified log file.')
		);

		$this->getOutput()->setProcessor(new ColorProcessor);

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

		// Register the global dispatcher
		$this->setDispatcher(new Dispatcher);
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

		$composerCfg = json_decode(file_get_contents(JPATH_ROOT . '/composer.json'));

		$this->outputTitle(g11n3t('Joomla! Tracker CLI Application'), $composerCfg->version);

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

		$className = 'Application\\Command\\' . ucfirst($command) . '\\' . ucfirst($action);

		if (false == class_exists($className))
		{
			$this->out()
				->out(sprintf(g11n3t('Invalid command: %s'), '<error> ' . (($command == $action) ? $command : $command . ' ' . $action) . ' </error>'))
				->out();

			$alternatives = $this->getAlternatives($command, $action);

			if (count($alternatives))
			{
				$this->out('<b>' . g11n3t('Did you mean one of this?') . '</b>')
					->out('    <question> ' . implode(' </question>    <question> ', $alternatives) . ' </question>');

				return;
			}

			$className = 'Application\\Command\\Help\\Help';
		}

		if (false == method_exists($className, 'execute'))
		{
			throw new \RuntimeException(sprintf('Missing method %1$s::%2$s', $className, 'execute'));
		}

		try
		{
			/* @type TrackerCommand $command */
			$command = new $className;

			if ($command instanceof ContainerAwareInterface)
			{
				$command->setContainer($this->container);
			}

			$command->execute();
		}
		catch (AbortException $e)
		{
			$this->out('')
				->out('<comment>' . g11n3t('Process aborted.') . '</comment>');
		}

		$this->out()
			->out(str_repeat('_', 40))
			->out(
				sprintf(
					g11n3t('Execution time: <b>%d sec.</b>'),
					time() - $this->get('execution.timestamp')
				)
			)
			->out(str_repeat('_', 40));
	}

	/**
	 * Get alternatives for a not found command or action.
	 *
	 * @param   string  $command  The command.
	 * @param   string  $action   The action.
	 *
	 * @return  array
	 *
	 * @since   1.0
	 */
	protected function getAlternatives($command, $action)
	{
		$commands = (new Help)->setContainer($this->getContainer())->getCommands();

		$alternatives = [];

		if (false == array_key_exists($command, $commands))
		{
			// Unknown command
			foreach (array_keys($commands) as $cmd)
			{
				if (levenshtein($cmd, $command) <= strlen($cmd) / 3 || false !== strpos($cmd, $command))
				{
					$alternatives[] = $cmd;
				}
			}
		}
		else
		{
			// Known command - unknown action
			$actions = (new Help)->setContainer($this->getContainer())->getActions($command);

			foreach (array_keys($actions) as $act)
			{
				if (levenshtein($act, $action) <= strlen($act) / 3 || false !== strpos($act, $action))
				{
					$alternatives[] = $command . ' ' . $act;
				}
			}
		}

		return $alternatives;
	}

	/**
	 * Get the dispatcher object.
	 *
	 * @return  Dispatcher
	 *
	 * @since   1.0
	 */
	public function getDispatcher()
	{
		return $this->dispatcher;
	}

	/**
	 * Set the dispatcher to use.
	 *
	 * @param   DispatcherInterface  $dispatcher  The dispatcher to use.
	 *
	 * @return  $this  Method allows chaining
	 *
	 * @since   1.0
	 */
	public function setDispatcher(DispatcherInterface $dispatcher)
	{
		$this->dispatcher = $dispatcher;

		return $this;
	}

	/**
	 * Write a string to standard output.
	 *
	 * @param   string   $text     The text to display.
	 * @param   boolean  $newline  True (default) to append a new line at the end of the output string.
	 *
	 * @return  $this
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
	 * @return  $this
	 *
	 * @since   1.0
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
	 * @return  $this
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
	 * Get a user object.
	 *
	 * Some methods check for an authenticated user...
	 *
	 * @return  GitHubUser
	 *
	 * @since   1.0
	 */
	public function getUser()
	{
		// Urgh..
		$user = new GitHubUser(
			new TrackerProject($this->container->get('db')),
			$this->container->get('db')
		);
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

		$rate = $this->container->get('gitHub')->authorization->getRateLimit()->resources->core;

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

	/**
	 * This is a useless legacy function.
	 *
	 * Actually it's accessed by the \JTracker\Model\AbstractTrackerListModel
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
	 * Load a foreign language.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	protected function loadLanguage()
	{
		// Get the language tag from user input.
		$lang = $this->input->get('lang');

		if ($lang)
		{
			if (false == in_array($lang, $this->get('languages')))
			{
				// Unknown language from user input - fall back to default
				$lang = g11n::getDefault();
			}

			if (false == in_array($lang, $this->get('languages')))
			{
				// Unknown default language - Fall back to British.
				$lang = 'en-GB';
			}
		}
		else
		{
			$lang = g11n::getCurrent();

			if (false == in_array($lang, $this->get('languages')))
			{
				// Unknown current language - Fall back to British.
				$lang = 'en-GB';
			}
		}

		if ($lang)
		{
			// Set the current language if anything has been found.
			g11n::setCurrent($lang);
		}

		// Set language debugging.
		g11n::setDebug($this->get('debug.language'));

		// Set the language cache directory.
		if ('vagrant' == getenv('JTRACKER_ENVIRONMENT'))
		{
			g11n::setCacheDir('/tmp');
		}
		else
		{
			g11n::setCacheDir(JPATH_ROOT . '/cache');
		}

		// Load the CLI language file.
		g11n::addDomainPath('CLI', JPATH_ROOT);
		g11n::loadLanguage('cli', 'CLI');

		return $this;
	}
}
