<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace CliApp\Command\Test;

use CliApp\Command\TrackerCommand;

use Joomla\Filesystem\Folder;

/**
 * Base class for running tests.
 *
 * @since  1.0
 */
class Test extends TrackerCommand
{
	/**
	 * The command "description" used for help texts.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $description = 'The test engine';

	/**
	 * Should the command exit or return the status.
	 *
	 * @var bool
	 * @since  1.0
	 */
	protected $exit = true;

	/**
	 * Execute the command.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		$this->getApplication()->outputTitle('Test');

		$errorTitle = 'Please use one of the following:';

		$this->out('<error>                                    </error>');
		$this->out('<error>  ' . $errorTitle . '  </error>');

		foreach (Folder::files(__DIR__) as $file)
		{
			$cmd = strtolower(substr($file, 0, strlen($file) - 4));

			if ('test' == $cmd)
			{
				continue;
			}

			$this->out('<error>  test ' . $cmd . str_repeat(' ', strlen($errorTitle) - strlen($cmd) - 3) . '</error>');
		}

		$this->out('<error>                                    </error>');
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
