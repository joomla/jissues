<?php
/**
 * @copyright  Copyright (C) 2013 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CliApp\Command\Make;

use CliApp\Command\TrackerCommand;

/**
 * Class for retrieving issues from GitHub for selected projects
 *
 * @since  1.0
 */
class Autocomplete extends Make
{
	/**
	 * Constructor.
	 *
	 * @since   1.0
	 */
	public function __construct()
	{
		parent::__construct();

		$this->description = 'Generate an auto complete file for PHPStorm.';
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
		$this->application->outputTitle('Make Auto complete');

		$cliBase = JPATH_ROOT . '/cli/CliApp/Command';

		$helper = new Helper($this->application);

		$xml = simplexml_load_string(
			'<framework xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"'
			. ' xsi:noNamespaceSchemaLocation="schemas/frameworkDescriptionVersion1.1.3.xsd"'
			. ' name="Custom_jtracker" invoke="cli/tracker.php" alias="jtracker" enabled="true" version="2">'
			. '</framework>'
		);

		/* @type \DirectoryIterator $fileInfo */
		foreach (new \DirectoryIterator($cliBase) as $fileInfo)
		{
			if ($fileInfo->isDot())
			{
				continue;
			}

			if ($fileInfo->isDir())
			{
				$command = $fileInfo->getFilename();

				$commandName = '\\CliApp\\Command\\' . $command;

				$className = $commandName . '\\' . $command;

				/* @type TrackerCommand $class */
				$class = new $className($this->application);

				$help = $class->getDescription();
				$help = str_replace(array('<cmd>', '</cmd>', '<', '>'), '', $help);

				$xmlCommand = $xml->addChild('command');

				$xmlCommand->addChild('name', strtolower($command));
				$xmlCommand->addChild('help', $help);

				if (false == in_array($command, array('Help', 'Install')))
				{
					$xmlCommand->addChild('params', 'option');
				}

				$actions = $helper->getActions($command);

				/* @type TrackerCommand $option */
				foreach ($actions as $name => $option)
				{
					$help = $option->getDescription();

					$xmlCommand = $xml->addChild('command');
					$xmlCommand->addChild('name', strtolower($command) . ' ' . strtolower($name));
					$xmlCommand->addChild('help', $help);
				}
			}
		}

		$doc = new \DOMDocument('1.0', 'UTF-8');
		$doc->formatOutput = true;

		$domNode = dom_import_simplexml($xml);
		$domNode = $doc->importNode($domNode, true);

		$doc->appendChild($domNode);

		echo $doc->saveXML();

		$this->out()
			->out('Finished =;)');
	}
}

/**
 * Class Helper.
 *
 * Dummy class to expose a protected method.
 *
 * @since  1.0
 */
class Helper extends \CliApp\Command\Help\Help
{
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
		return parent::getActions($commandName);
	}
}
