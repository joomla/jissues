<?php
/**
 * @package     JTracker
 * @subpackage  CLI
 *
 * @copyright   Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CliApp\Command\Help;

use CliApp\Command\TrackerCommand;

/**
 * Class Help.
 *
 * @since  1.0
 */
class Help extends TrackerCommand
{
	/**
	 * Execute the command.
	 *
	 * @return void
	 */
	public function execute()
	{
		$commands = $this->getCommands();

		var_dump($commands);
	}

	/**
	 * Get the available commands.
	 *
	 * @return array
	 */
	protected function getCommands()
	{
		$commands = array();

		/* @var \DirectoryIterator $fileInfo */
		foreach (new \DirectoryIterator(__DIR__ . '/..') as $fileInfo)
		{
			if ($fileInfo->isDot() || $fileInfo->isFile())
			{
				continue;
			}

			$this->out($fileInfo->getFilename());

			$command = new \stdClass;

			$commands[strtolower($fileInfo->getFilename())] = $command;
		}

		return $commands;
	}
}
