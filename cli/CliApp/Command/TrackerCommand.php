<?php
/**
 * User: elkuku
 * Date: 24.04.13
 * Time: 18:31
 */

namespace CliApp\Command;

use Joomla\Application\AbstractCliApplication;
use Joomla\Database\DatabaseDriver;

abstract class TrackerCommand
{
	/**
	 * @var AbstractCliApplication
	 */
	protected $application;

	/**
	 * Database object
	 *
	 * @var    DatabaseDriver
	 * @since  1.0
	 */
	protected $db;

	/**
	 * The application input object.
	 *
	 * @var    \Joomla\Input\Input
	 * @since  1.0
	 */
	protected $input;

	public function __construct(AbstractCliApplication $application)
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
