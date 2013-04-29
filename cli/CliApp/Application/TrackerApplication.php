<?php
/**
 * @package     JTracker
 * @subpackage  CLI
 *
 * @copyright   Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CliApp\Application;

use Joomla\Application\AbstractCliApplication;
use Joomla\Input;
use Joomla\Registry\Registry;
use Joomla\Database\DatabaseDriver;

use CliApp\Command\TrackerCommand;
use CliApp\Command\TrackerCommandOption;
use CliApp\Exception\AbortException;

/**
 * Simple Installer.
 *
 * @package     JTracker
 * @subpackage  CLI
 * @since       1.0
 */
class TrackerApplication extends AbstractCliApplication
{
	/**
	 * @var  DatabaseDriver
	 */
	private $database = null;

	/**
	 * Quiet mode - no output.
	 *
	 * @var bool
	 */
	private $quiet = false;

	/**
	 * Verbose mode - debug output.
	 *
	 * @var bool
	 */
	private $verbose = false;

	/**
	 * @var array
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

		$this->loadConfiguration();
	}

	/**
	 * Get a database driver object.
	 *
	 * @return DatabaseDriver
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
	 * @throws \RuntimeException
	 * @return  void
	 *
	 * @since   1.0
	 */
	protected function doExecute()
	{
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

		$className = 'CliApp\\Command\\' . ucfirst($command) . '\\' . ucfirst($action);

		if (false == class_exists($className))
		{
			$this->out()
				->out('Invalid command: ' . (($command == $action) ? $command : $command . ' ' . $action))
				->out();

			$className = 'CliApp\\Command\\Help\\Help';
		}

		if (false == method_exists($className, 'execute'))
		{
			throw new \RuntimeException(sprintf('Missing method %1$s::%2$s', $className, 'execute'));
		}

		$this->quiet   = $this->input->get('quiet', $this->input->get('q'));
		$this->verbose = $this->input->get('verbose', $this->input->get('v'));

		/* @var TrackerCommand $class */
		$class = new $className($this);

		try
		{
			$class->execute();
		}
		catch (AbortException $e)
		{
			$this->out('')
				->out('Process aborted.');
		}

		$this->out()
			->out(str_repeat('_', 40))
			->out(
				sprintf(
					'Execution time: %d sec.',
					time() - $this->get('execution.timestamp')
				)
			)
			->out(str_repeat('_', 40));
	}

	/**
	 * Write a string to standard output.
	 *
	 * @param   string   $text  The text to display.
	 * @param   boolean  $nl    True (default) to append a new line at the end of the output string.
	 *
	 * @return  TrackerApplication
	 *
	 * @codeCoverageIgnore
	 * @since   1.0
	 */
	public function out($text = '', $nl = true)
	{
		return ($this->quiet) ? $this : parent::out($text, $nl);
	}

	/**
	 * Write a string to standard output in "verbose" mode.
	 *
	 * @param   string  $text  The text to display.
	 *
	 * @return TrackerApplication
	 */
	public function debugOut($text)
	{
		return ($this->verbose) ? $this->out('DEBUG ' . $text) : $this;
	}

	/**
	 * Output a nicely formatted title for the application.
	 *
	 * @param   string  $title     The title to display.
	 * @param   string  $subTitle  A subtitle
	 * @param   int     $width     Total width in chars
	 *
	 * @return TrackerApplication
	 */
	public function outputTitle($title, $subTitle = '', $width = 40)
	{
		$this->out(str_repeat('-', $width));

		$this->out(str_repeat(' ', $width / 2 - (strlen($title) / 2)) . $title);

		if ($subTitle)
		{
			$this->out(str_repeat(' ', $width / 2 - (strlen($subTitle) / 2)) . $subTitle);
		}

		$this->out(str_repeat('-', $width));

		return $this;
	}

	/**
	 * Load the application configuration.
	 *
	 * @throws \RuntimeException
	 *
	 * @return $this
	 */
	protected function loadConfiguration()
	{
		// Instantiate variables.
		$config = array();

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
	 * @return array
	 */
	public function getCommandOptions()
	{
		return $this->commandOptions;
	}

	/**
	 * This is a useless legacy function.
	 *
	 * @todo remove
	 *
	 * @return string
	 */
	public function getUserStateFromRequest()
	{
		return '';
	}
}
