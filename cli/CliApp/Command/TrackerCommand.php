<?php
/**
 * User: elkuku
 * Date: 24.04.13
 * Time: 18:31
 */

namespace CliApp\Command;

use Joomla\Database\DatabaseDriver;

use CliApp\TrackerApplication;

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

	public function __construct(TrackerApplication $application)
	{
		$this->application = $application;

		$this->input = $application->input;
	}

	abstract public function execute();

	protected function out($text = '', $nl = true)
	{
		$this->application->out($text, $nl);

		return $this;
	}
}
