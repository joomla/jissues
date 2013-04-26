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
use CliApp\Command\TrackerCommandOption;

/**
 * Class Help.
 *
 * @since  1.0
 */
class Help extends TrackerCommand
{
	protected $description = 'Displays helpful information.';

	private $commands = array();

	/**
	 * Execute the command.
	 *
	 * @return void
	 */
	public function execute()
	{
		$this->commands = $this->getCommands();

		if (isset($this->input->args[1]))
		{
			$this->helpCommand($this->input->args[1]);

			return;
		}

		$this->out(
			sprintf('Usage: %s [command]',
				basename($this->application->input->executable)
			)
		);

		$this->out()
			->out('Available commands:')
			->out();

		/* @var  TrackerCommand $command */
		foreach ($this->commands as $cName => $command)
		{
			$this->out($cName);

			if ($command->description)
			{
				$this->out('    ' . $command->description);
			}

			$this->out();
		}

		$this->out('Form more information use "help [command]".');
	}

	/**
	 * Get help on a command.
	 *
	 * @param   string  $command  The command.
	 *
	 * @return void
	 */
	protected function helpCommand($command)
	{
		if (false == array_key_exists($command, $this->commands))
		{
			$this->out()
				->out('Unknown command: ' . $command);

			return;
		}

		/* @var TrackerCommand $c */
		$c = $this->commands[$command];

		$this->out('Command: ' . $command)
			->out()
			->out($c->description);

		if ($c->options)
		{
			$this->out()
				->out('Available options:');

			/* @var TrackerCommandOption $option */
			foreach ($c->options as $option)
			{
				$this->out()
					->out(
						'--' . $option->longArg
							. ($option->shortArg ? ' [-' . $option->shortArg . ']' : '')
					)
					->out('    ' . $option->description);
			}
		}
		else
		{
			$this->out()
				->out('No further options available.');
		}
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

			$c = $fileInfo->getFilename();

			if ('Help' == $c)
			{
				$command = clone($this);
			}
			else
			{
				$className = "CliApp\\Command\\$c\\$c";
				$command   = new $className($this->application);
			}

			$commands[strtolower($fileInfo->getFilename())] = $command;
		}

		return $commands;
	}
}
