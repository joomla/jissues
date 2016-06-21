<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Application\Command\Help;

use Application\Command\TrackerCommand;
use Application\Command\TrackerCommandOption;

use Joomla\Application\Cli\Output\Processor\ColorProcessor;
use Joomla\Application\Cli\ColorStyle;

/**
 * Class for displaying help data for the installer application.
 *
 * @since  1.0
 */
class Help extends TrackerCommand
{
	/**
	 * Array containing the available commands
	 *
	 * @var    array
	 * @since  1.0
	 */
	private $commands = array();

	/**
	 * Constructor.
	 *
	 * @since   1.0
	 */
	public function __construct()
	{
		$this->description = g11n3t('Displays helpful information');
	}

	/**
	 * Execute the command.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		/* @type ColorProcessor $processor */
		$processor = $this->getApplication()->getOutput()->getProcessor();

		$processor
			->addStyle('cmd', new ColorStyle('magenta'))
			->addStyle('opt', new ColorStyle('cyan'));

		$executable = basename($this->getApplication()->input->executable);

		$this->commands = $this->getCommands();

		if (isset($this->getApplication()->input->args[1]))
		{
			$this->helpCommand($this->getApplication()->input->args[1]);

			return;
		}

		$this->out(
			sprintf(
				g11n3t('<b>Usage</b>: %s'), sprintf(' <info>%s</info> <cmd><' . g11n3t('command') . '></cmd> <opt>[' . g11n3t('options') . ']</opt>',
				$executable
				)
			)
		);

		$this->out()
			->out(g11n3t('Available commands:'))
			->out();

		/* @type  TrackerCommand $command */
		foreach ($this->commands as $cName => $command)
		{
			$this->out('<cmd>' . $cName . '</cmd>');

			if ($command->getDescription())
			{
				$this->out('    ' . $command->getDescription());
			}

			$this->out();
		}

		$this->out(sprintf(g11n3t('<b>For more information use</b>: %s'), ' <info>' . $executable . ' help</info> <cmd><' . g11n3t('command') . '></cmd>.'))
			->out();

		$options = $this->getApplication()->getCommandOptions();

		if ($options)
		{
			$this->out(g11n3t('Application command <opt>options</opt>'));

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
				// TRANSLATORS: %s refers to a command name
				->out(sprintf(g11n3t('Unknown: %s'), $command));

			return;
		}

		$actions = $this->getActions($command);

		/* @type TrackerCommand $c */
		$c = $this->commands[$command];

		$this->out(sprintf(g11n3t('Command: %s'), ' <b>' . $command . '</b>' . ($actions ? ' <cmd><' . g11n3t('action') . '></cmd>' : '')))
			->out()
			->out('    ' . $c->getDescription());

		if ($c->options)
		{
			$this->out()
				->out('  ' . g11n3t('Available options:'));

			foreach ($c->options as $option)
			{
				$this->displayOption($option);
			}
		}

		if ($actions)
		{
			$this->out()
				->out('  ' . g11n3t('Available <cmd>actions</cmd>:'))
			->out();

			/* @type TrackerCommand $action */
			foreach ($actions as $aName => $action)
			{
				$this->out('<cmd>' . $aName . '</cmd>')
					->out('    ' . $action->getDescription());

				if ($action->options)
				{
					$this->out()
						->out('  ' . g11n3t('Available options:'));

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
	 * @return  $this
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
	 * @throws  \RuntimeException
	 */
	public function getCommands()
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

			$className = "Application\\Command\\$c\\$c";

			if (false == class_exists($className))
			{
				throw new \RuntimeException(sprintf('Required class "%s" not found.', $className));
			}

			$commands[strtolower($c)] = new $className($this->getContainer());
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
	public function getActions($commandName)
	{
		$actions = array();
		$cName = ucfirst($commandName);

		/* @var \DirectoryIterator $fileInfo */
		foreach (new \DirectoryIterator(__DIR__ . '/../' . $cName) as $fileInfo)
		{
			if ($fileInfo->isDot() || $fileInfo->isDir())
			{
				continue;
			}

			$c = $fileInfo->getFilename();

			$action = substr($c, 0, strrpos($c, '.'));

			if ($action != $cName)
			{
				$className = "Application\\Command\\$cName\\$action";

				$actions[strtolower($action)] = new $className($this->getContainer());
			}
		}

		return $actions;
	}
}
