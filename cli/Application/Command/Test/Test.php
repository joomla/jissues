<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Application\Command\Test;

use Application\Command\TrackerCommand;

/**
 * Base class for running tests.
 *
 * @since  1.0
 */
class Test extends TrackerCommand
{
	/**
	 * Should the command exit or return the status.
	 *
	 * @var    boolean
	 * @since  1.0
	 */
	protected $exit = true;

	/**
	 * Constructor.
	 *
	 * @since   1.0
	 */
	public function __construct()
	{
		$this->description = g11n3t('The test engine');
	}

	/**
	 * Execute the command.
	 *
	 * NOTE: This command must not be executed without parameters !
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		return $this->displayMissingOption(__DIR__);
	}

	/**
	 * Set the exit behavior.
	 *
	 * @param   boolean  $value  Exit behavior. True to exit, false to return the status.
	 *
	 * @return  $this
	 */
	public function setExit($value)
	{
		$this->exit = (boolean) $value;

		return $this;
	}
}
