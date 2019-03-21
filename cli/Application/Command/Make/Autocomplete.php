<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Application\Command\Make;

use Application\Command\Help\Help;
use Application\Command\TrackerCommand;
use Application\Command\TrackerCommandOption;

use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;

/**
 * Class for generating a PhpStorm autocomplete file for using the CLI tools
 *
 * @since  1.0
 */
class Autocomplete extends Make
{
	/**
	 * @var boolean
	 */
	private $echo = false;

	/**
	 * @var Filesystem
	 */
	private $fileSystem = null;

	/**
	 * Array of known auto complete file types.
	 *
	 * @var array
	 */
	private $knownTypes = ['phpstorm', 'fish'];

	/**
	 * Constructor.
	 *
	 * @since   1.0
	 */
	public function __construct()
	{
		parent::__construct();

		$this->description = 'Generate autocomplete files.';

		$this
			->addOption(
				new TrackerCommandOption(
					'type',
					't',
					sprintf('The type of auto complete file (currently supported: %s).', "'" . implode("' '", $this->knownTypes) . "'")
				)
			)
			->addOption(
				new TrackerCommandOption(
					'echo',
					'e',
					'Echo the output instead of writing it to a file.'
				)
			);
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
		$this->getApplication()->outputTitle('Make Auto complete');

		$commands = $this->getCommands();
		$applicationOptions = $this->getApplication()->getCommandOptions();

		$type = $this->getOption('type');
		$this->echo = (boolean) $this->getOption('echo');

		$this->fileSystem = new Filesystem(new Local(JPATH_ROOT));

		if ($type)
		{
			if (false === in_array($type, $this->knownTypes))
			{
				throw new \InvalidArgumentException(sprintf('Invalid type supplied. Valid types are: %s', "'" . implode("' '", $this->knownTypes) . "'"));
			}

			$command = 'make' . $type;
			$this->$command($commands, $applicationOptions);
		}
		else
		{
			foreach ($this->knownTypes as $type)
			{
				$command = 'make' . $type;
				$this->$command($commands, $applicationOptions);
			}
		}

		$this->out()
			->out('Finished.');

		if (false)
		{
			// This is here just to make our IDEs happy :P
			$this->makeFish($commands, $applicationOptions);
			$this->makePhpStorm($commands);
		}
	}

	/**
	 * Make a PHPStorm auto complete file.
	 *
	 * @param   array  $commands  Available commands.
	 *
	 * @return $this
	 */
	private function makePhpStorm(array $commands)
	{
		$xml = simplexml_load_string(
			'<framework xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"'
			. ' xsi:noNamespaceSchemaLocation="schemas/frameworkDescriptionVersion1.1.4.xsd"'
			. ' name="Custom_jtracker" invoke="cli/tracker.php" alias="jt" enabled="true" version="2">'
			. '</framework>'
		);

		foreach ($commands as $command)
		{
			$xmlCommand = $xml->addChild('command');

			$xmlCommand->addChild('name', $command->name);
			$xmlCommand->addChild('help', $command->description);

			if (false === in_array($command->name, ['help', 'install']))
			{
				$xmlCommand->addChild('params', 'option');
			}

			/** @var TrackerCommand $action */
			foreach ($command->actions as $name => $action)
			{
				$help = str_replace(['<cmd>', '</cmd>', '<', '>'], '', $action->getDescription());

				$xmlCommand = $xml->addChild('command');
				$xmlCommand->addChild('name', $command->name . ' ' . strtolower($name));
				$xmlCommand->addChild('help', $help);
			}
		}

		$doc = new \DOMDocument('1.0', 'UTF-8');
		$doc->formatOutput = true;

		$domNode = dom_import_simplexml($xml);
		$domNode = $doc->importNode($domNode, true);

		$doc->appendChild($domNode);

		$contents = $doc->saveXML();

		if ($this->echo)
		{
			echo $contents;
		}
		else
		{
			$path = 'cli/completions/Custom_jtracker.xml';

			if (!$this->fileSystem->put($path, $contents))
			{
				throw new \RuntimeException('Can not write to path.');
			}

			$this->out(strtr('File has been written to: %path%', ['%path%' => $path]));
		}

		return $this;
	}

	/**
	 * Make a fish auto complete file.
	 *
	 * @param   array  $commands            Available commands.
	 * @param   array  $applicationOptions  Available application options.
	 *
	 * @return $this
	 */
	private function makeFish(array $commands, array $applicationOptions)
	{
		$template = $this->fileSystem->read('cli/completions/tpl_jtracker.fish');
		$lines = [];

		foreach ($commands as $command)
		{
			$lines[] = "# jtracker $command->name";
			$lines[] = "complete -f -c jtracker -n '__fish_jtracker_needs_command' -a $command->name -d \"$command->description\"";

			/** @var TrackerCommand $action */
			foreach ($command->actions as $name => $action)
			{
				$description = str_replace(['<cmd>', '</cmd>', '<', '>'], '', $action->getDescription());

				$lines[] = "complete -f -c jtracker -n '__fish_jtracker_using_command $command->name' -a $name -d \"$description\"";

				if ($action->options)
				{
					foreach ($action->options as $option)
					{
						$lines[] = $this->formatOption($option, $command->name, $name);
					}
				}

				if ($applicationOptions)
				{
					foreach ($applicationOptions as $option)
					{
						$lines[] = $this->formatOption($option, $command->name, $name);
					}
				}
			}

			$lines[] = '';
		}

		$contents = $template . "\n" . implode("\n", $lines);

		if ($this->echo)
		{
			echo $contents;
		}
		else
		{
			$path = 'cli/completions/jtracker.fish';

			if (!$this->fileSystem->put($path, $contents))
			{
				throw new \RuntimeException('Can not write to path.');
			}

			$this->out(strtr('File has been written to: %path%', ['%path%' => $path]));
		}

		return $this;
	}

	/**
	 * get known commands.
	 *
	 * @return array
	 */
	private function getCommands()
	{
		$commands = [];
		$names = [];

		$helper = new Help;
		$helper->setContainer($this->getContainer());

		/** @var \DirectoryIterator $fileInfo */
		foreach (new \DirectoryIterator(JPATH_ROOT . '/cli/Application/Command') as $fileInfo)
		{
			if ($fileInfo->isDot())
			{
				continue;
			}

			if ($fileInfo->isDir())
			{
				$names[] = $fileInfo->getFilename();
			}
		}

		// Sort the array and put the "Help" command in first place.
		usort(
			$names, function ($a, $b)
			{
				if ('Help' == $a)
				{
					return -1;
				}
				elseif ('Help' == $b)
				{
					return 1;
				}

				return strcmp($a, $b);
			}
		);

		foreach ($names as $name)
		{
			$command = new \stdClass;
			$commandName = '\\Application\\Command\\' . $name;

			$className = $commandName . '\\' . $name;

			/** @var TrackerCommand $class */
			$class = new $className($this->getApplication());
			$class->setContainer($this->getContainer());

			$command->name = strtolower($name);
			$command->description = str_replace(['<cmd>', '</cmd>', '<', '>'], '', $class->getDescription());

			$actions = $helper->getActions($command->name);

			ksort($actions);

			$command->actions = $actions;

			$commands[] = $command;
		}

		return $commands;
	}

	/**
	 * Display a command option.
	 *
	 * @param   TrackerCommandOption  $option   The command option.
	 * @param   string                $command  The command name.
	 * @param   string                $action   The action name.
	 *
	 * @return string
	 */
	private function formatOption(TrackerCommandOption $option, $command, $action)
	{
		$shortArg = $option->shortArg ? ' -s ' . $option->shortArg : '';
		$longArg = $option->longArg ? ' -l ' . $option->longArg : '';

		return "complete -f -c jtracker -n '__fish_jtracker_using_action $command $action'$shortArg$longArg -d \"$option->description\"";
	}
}
