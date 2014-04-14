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

/**
 * Class for generating a PhpStorm autocomplete file for using the CLI tools
 *
 * @since  1.0
 */
class Autocomplete extends Make
{
	/**
	 * The command "description" used for help texts.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $description = 'Generate an auto complete file for PhpStorm';

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

		$cliBase = JPATH_ROOT . '/cli/Application/Command';

		$helper = new Help;
		$helper->setContainer($this->getContainer());

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

				$commandName = '\\Application\\Command\\' . $command;

				$className = $commandName . '\\' . $command;

				/* @type TrackerCommand $class */
				$class = new $className($this->getApplication());
				$class->setContainer($this->getContainer());

				$help = str_replace(array('<cmd>', '</cmd>', '<', '>'), '', $class->getDescription());

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
					$help = str_replace(array('<cmd>', '</cmd>', '<', '>'), '', $option->getDescription());

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

		$contents = $doc->saveXML();

		$fileName = $this->getApplication()->input->getPath('file', $this->getApplication()->input->getPath('f'));

		if ($fileName)
		{
			$this->out('Writing contents to: ' . $fileName);

			file_put_contents($fileName, $contents);
		}
		else
		{
			echo $contents;
		}

		$this->out()
			->out('Finished =;)');
	}
}
