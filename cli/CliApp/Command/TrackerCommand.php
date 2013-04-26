<?php
/**
 * @package     JTracker
 * @subpackage  CLI
 *
 * @copyright   Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CliApp\Command;

use Joomla\Database\DatabaseDriver;

use CliApp\Application\TrackerApplication;

/**
 * TrackerCommand class
 *
 * @since  1.0
 */
abstract class TrackerCommand
{
	/**
	 * @var TrackerApplication
	 */
	protected $application;

	/**
	 * The application input object.
	 *
	 * @var    \Joomla\Input\Input
	 * @since  1.0
	 */
	protected $input;

	/**
	 * Constructor.
	 *
	 * @param   TrackerApplication  $application  The application object.
	 */
	public function __construct(TrackerApplication $application)
	{
		$this->application = $application;

		$this->input = $application->input;
	}

	/**
	 * Execute the command.
	 *
	 * @return void
	 */
	abstract public function execute();

	/**
	 * Write a string to standard output.
	 *
	 * @param   string   $text  The text to display.
	 * @param   boolean  $nl    True (default) to append a new line at the end of the output string.
	 *
	 * @return  TrackerCommand
	 *
	 * @codeCoverageIgnore
	 * @since   1.0
	 */
	protected function out($text = '', $nl = true)
	{
		$this->application->out($text, $nl);

		return $this;
	}
}
