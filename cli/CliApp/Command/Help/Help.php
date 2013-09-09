<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CliApp\Command\Help;

use CliApp\Command\TrackerCommand;
use CliApp\Command\TrackerCommandOption;

use Joomla\Application\Cli\ColorProcessor;
use Joomla\Application\Cli\ColorStyle;

/**
 * Class for displaying help data for the installer application.
 *
 * @since  1.0
 */
class Help extends TrackerCommand
{
	/**
	 * The command "description" used for help texts.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $description = 'Displays helpful information.';

	/**
	 * Array containing the available commands
	 *
	 * @var    array
	 * @since  1.0
	 */
	private $commands = array();

	/**
	 * Execute the command.
	 *
	 * @return void
	 */
	public function execute()
	{
		/* @type ColorProcessor $processor */
		$processor = $this->application->getOutput()->getProcessor();

		$processor
			->addStyle('cmd', new ColorStyle('magenta'))
			->addStyle('opt', new ColorStyle('cyan'));

		$executable = basename($this->application->input->executable);

		$this->commands = $this->getCommands();

		if (isset($this->application->input->args[1]))
		{
			$this->helpCommand($this->application->input->args[1]);

			return;
		}

		$this->out(
			sprintf('<b>Usage:</b> <info>%s</info> <cmd><command></cmd> <opt>[options]</opt>',
				$executable
			)
		);

		$this->out()
			->out('Available commands:')
			->out();

		/* @type  TrackerCommand $command */
		foreach ($this->commands as $cName => $command)
		{
			$this->out('<cmd>' . $cName . '</cmd>');

			if ($command->description)
			{
				$this->out('    ' . $command->description);
			}

			$this->out();
		}

		$this->out('<b>For more information use</b> <info>' . $executable . ' help</info> <cmd><command></cmd>.')
			->out();

		$options = $this->application->getCommandOptions();

		if ($options)
		{
			$this->out('Application command <opt>options</opt>');

			foreach ($options as $option)
			{
				$this->displayOption($option);
			}
		}
	}

	/**
	 * Get help on a command.
	 *
	 * @param   string  $command  The command.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	protected function helpCommand($command)
	{
		if (false == array_key_exists($command, $this->commands))
		{
			$this->out()
				->out('Unknown command: ' . $command);

			return;
		}

		$actions = $this->getActions($command);

		/* @type TrackerCommand $c */
		$c = $this->commands[$command];

		$this->out('Command: <b>' . $command . '</b>' . ($actions ? ' <cmd><action></cmd>' : ''))
			->out()
			->out('    ' . $c->description);

		if ($c->options)
		{
			$this->out()
				->out('  Available options:');

			foreach ($c->options as $option)
			{
				$this->displayOption($option);
			}
		}

		if ($actions)
		{
			$this->out()
				->out('  Available <cmd>actions</cmd>:')
			->out();

			foreach ($actions as $aName => $action)
			{
				$this->out('<cmd>' . $aName . '</cmd>')
					->out('    ' . $action->description);

				if ($action->options)
				{
					$this->out()
						->out('  Available options:');

					foreach ($action->options as $option)
					{
						$this->displayOption($option);
					}
				}
			}
		}
	}

	/**
	 * Display a command option.
	 *
	 * @param   TrackerCommandOption  $option  The command option.
	 *
	 * @return  TrackerCommand
	 *
	 * @since   1.0
	 */
	private function displayOption(TrackerCommandOption $option)
	{
		return $this->out()
			->out(
				($option->shortArg ? '<opt>-' . $option->shortArg . '</opt> | ' : '')
				. '<opt>--' . $option->longArg . '</opt>'
			)
			->out('    ' . $option->description);
	}

	/**
	 * Get the available commands.
	 *
	 * @return  array
	 *
	 * @since   1.0
	 */
	private function getCommands()
	{
		$commands = array();

		/* @type \DirectoryIterator $fileInfo */
		foreach (new \DirectoryIterator(__DIR__ . '/..') as $fileInfo)
		{
			if ($fileInfo->isDot() || $fileInfo->isFile())
			{
				continue;
			}

			$c = $fileInfo->getFilename();

			$className = "CliApp\\Command\\$c\\$c";

			$commands[strtolower($c)] = new $className($this->application);
		}

		return $commands;
	}

	/**
	 * Get available actions for a command.
	 *
	 * @param   string  $commandName  The command name.
	 *
	 * @return  array
	 *
	 * @since   1.0
	 */
	protected function getActions($commandName)
	{
		$actions = array();
		$cName = ucfirst($commandName);

		/* @var \DirectoryIterator $fileInfo */
		foreach (new \DirectoryIterator(__DIR__ . '/../' . $cName) as $fileInfo)
		{
			if ($fileInfo->isDot())
			{
				continue;
			}

			$c = $fileInfo->getFilename();

			$action = substr($c, 0, strrpos($c, '.'));

			if ($action != $cName)
			{
				$className = "CliApp\\Command\\$cName\\$action";

				$actions[strtolower($action)] = new $className($this->application);
			}
		}

		return $actions;
	}
}
