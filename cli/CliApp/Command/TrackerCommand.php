<?php
/**
 * @package     JTracker
 * @subpackage  CLI
 *
 * @copyright   Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CliApp\Command;

use JTracker\Container;

/**
 * TrackerCommand class
 *
 * @since  1.0
 */
abstract class TrackerCommand
{
	/**
	 * @var    \CliApp\Application\CliApplication
	 * @since  1.0
	 */
	protected $application;

	/**
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
	 * Constructor.
	 *
	 * @since   1.0
	 */
	public function __construct()
	{
		$this->application = Container::retrieve('app');
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
	 * @return  TrackerCommand
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
	 * Write a string to the standard output if an operation has terminated successfully.
	 *
	 * @return $this
	 *
	 * @since   1.0
	 */
	protected function outOK()
	{
		return $this->out('<ok>ok</ok>');
	}
}
