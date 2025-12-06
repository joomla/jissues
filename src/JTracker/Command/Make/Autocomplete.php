<?php

/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Command\Make;

use Joomla\Console\Command\AbstractCommand;
use Joomla\Console\Descriptor\ApplicationDescription;
use JTracker\Command\TrackerCommand;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\OutputStyle;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class for generating a PhpStorm autocomplete file for using the CLI tools
 *
 * @since  1.0
 */
class Autocomplete extends TrackerCommand
{
    /**
     * @var boolean
     */
    private $echo = false;

    /**
     * @var Filesystem
     */
    private $fileSystem;

    /**
     * Array of known auto complete file types.
     *
     * @var array
     */
    private $knownTypes = ['phpstorm', 'fish'];

    /**
     * Configure the command.
     *
     * @return  void
     *
     * @since   2.0.0
     */
    protected function configure(): void
    {
        $this->setName('make:autocomplete');
        $this->setDescription('Generate autocomplete files.');
        $this->addOption(
            'type',
            't',
            InputOption::VALUE_REQUIRED,
            \sprintf('The type of auto complete file (currently supported: %s).', "'" . implode("' '", $this->knownTypes) . "'")
        );

        $this->addOption(
            'echo',
            'e',
            InputOption::VALUE_NONE,
            'Echo the output instead of writing it to a file.'
        );
    }

    /**
     * Execute the command.
     *
     * @param   InputInterface   $input   The input to inject into the command.
     * @param   OutputInterface  $output  The output to inject into the command.
     *
     * @return  integer
     *
     * @throws  \RuntimeException
     * @throws  \UnexpectedValueException
     * @since   1.0
     */
    protected function doExecute(InputInterface $input, OutputInterface $output): int
    {
        $ioStyle = new SymfonyStyle($input, $output);
        $ioStyle->title('Make Auto complete');

        $description = new ApplicationDescription($this->getApplication(), '');
        $namespaces  = $description->getNamespaces();

        $type       = $input->getOption('type');
        $this->echo = (bool) $input->getOption('echo');

        $this->fileSystem = new Filesystem(new LocalFilesystemAdapter(JPATH_ROOT));

        if ($type) {
            if (\in_array($type, $this->knownTypes) === false) {
                throw new \InvalidArgumentException(\sprintf('Invalid type supplied. Valid types are: %s', "'" . implode("' '", $this->knownTypes) . "'"));
            }

            $command = 'make' . $type;
            $this->$command($namespaces, $description, $ioStyle);
        } else {
            foreach ($this->knownTypes as $type) {
                $command = 'make' . $type;
                $this->$command($namespaces, $description, $ioStyle);
            }
        }

        $ioStyle->newLine();
        $ioStyle->success('Finished.');

        if (false) {
            // This is here just to make our IDEs happy :P
            $this->makeFish($namespaces, $description, $ioStyle);
            $this->makePhpStorm($namespaces, $description, $ioStyle);
        }

        return Command::SUCCESS;
    }

    /**
     * Make a PHPStorm auto complete file.
     *
     * @param   array                   $namespaces   Available commands grouped by namespace.
     * @param   ApplicationDescription  $description  The description object.
     * @param   OutputStyle             $ioStyle      The renderer.
     *
     * @return $this
     */
    private function makePhpStorm(array $namespaces, ApplicationDescription $description, OutputStyle $ioStyle)
    {
        $xml = simplexml_load_string(
            '<framework xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"'
            . ' xsi:noNamespaceSchemaLocation="schemas/frameworkDescriptionVersion1.1.4.xsd"'
            . ' name="Custom_jtracker" invoke="cli/tracker.php" alias="jt" enabled="true" version="2">'
            . '</framework>'
        );

        foreach ($namespaces as $namespace) {
            $namespaceId = $namespace['id'];

            $xmlCommand = $xml->addChild('command');

            $xmlCommand->addChild('name', $namespaceId);

            if (\in_array($namespaceId, ['help', 'install', 'list']) === false) {
                $xmlCommand->addChild('params', 'option');
            }

            foreach ($namespace['commands'] as $command) {
                $commandObject = $description->getCommand($command);
                $help          = str_replace(['<cmd>', '</cmd>', '<', '>'], '', $commandObject->getDescription());

                $xmlCommand = $xml->addChild('command');
                $xmlCommand->addChild('name', $commandObject->getName());
                $xmlCommand->addChild('help', $help);
            }
        }

        $doc               = new \DOMDocument('1.0', 'UTF-8');
        $doc->formatOutput = true;

        $domNode = dom_import_simplexml($xml);
        $domNode = $doc->importNode($domNode, true);

        $doc->appendChild($domNode);

        $contents = $doc->saveXML();

        if ($this->echo) {
            echo $contents;
        } else {
            $path = 'cli/completions/Custom_jtracker.xml';

            if (!$this->fileSystem->put($path, $contents)) {
                throw new \RuntimeException('Can not write to path.');
            }

            $ioStyle->success(strtr('File has been written to: %path%', ['%path%' => $path]));
        }

        return $this;
    }

    /**
     * Make a fish auto complete file.
     *
     * @param   array                   $namespaces   Available commands grouped by namespace.
     * @param   ApplicationDescription  $description  The description object.
     * @param   OutputStyle             $ioStyle      The renderer.
     *
     * @return $this
     */
    private function makeFish(array $namespaces, ApplicationDescription $description, OutputStyle $ioStyle)
    {
        $template = $this->fileSystem->read('cli/completions/tpl_jtracker.fish');
        $lines    = [];

        /** @var string $namespace */
        /** @var string[] $commands */
        foreach ($namespaces as $namespace) {
            $namespaceId = $namespace['id'];

            if ($namespaceId !== '_global') {
                $lines[] = "# jtracker $namespaceId";
                $lines[] = "complete -f -c jtracker -n '__fish_jtracker_using_command list' -a $namespaceId";
            }

            foreach ($namespace['commands'] as $command) {
                $commandObject = $description->getCommand($command);
                $commandName   = $commandObject->getName();

                if ($namespaceId === '_global') {
                    $lines[] = "# jtracker $commandName";
                }

                $commandDescription = str_replace(['<cmd>', '</cmd>', '<', '>'], '', $commandObject->getDescription());
                $commandOptions     = $commandObject->getDefinition()->getOptions();

                $lines[] = "complete -f -c jtracker -n '__fish_jtracker_using_command' -a $commandName -d \"$commandDescription\"";

                if (!empty($commandOptions)) {
                    foreach ($commandOptions as $option) {
                        $lines[] = $this->formatOption($commandObject, $option);
                    }
                }
            }

            $lines[] = '';
        }

        $contents = $template . "\n" . implode("\n", $lines);

        if ($this->echo) {
            echo $contents;
        } else {
            $path = 'cli/completions/jtracker.fish';

            if (!$this->fileSystem->put($path, $contents)) {
                throw new \RuntimeException('Can not write to path.');
            }

            $ioStyle->success(strtr('File has been written to: %path%', ['%path%' => $path]));
        }

        return $this;
    }


    /**
     * Display a command option.
     *
     * @param   AbstractCommand $command  The command.
     * @param   InputOption     $option   The command option.
     *
     * @return string
     */
    private function formatOption(AbstractCommand $command, InputOption $option)
    {
        $shortArg     = $option->getShortcut() ? ' -s ' . $option->getShortcut() : '';
        $longArg      = $option->getName() ? ' -l ' . $option->getName() : '';
        $commandName  = $command->getName();
        $description  = $option->getDescription();

        return "complete -f -c jtracker -n '__fish_jtracker_using_action $commandName'$shortArg$longArg -d \"$description\"";
    }
}
