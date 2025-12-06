<?php

/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Command\Make;

use Clue\GraphComposer\Graph\GraphComposer;
use JTracker\Command\TrackerCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class for generating a graphical representation of the scripts that
 * have been installed using Composer and their dependencies.
 *
 * @since  1.0
 */
class Composergraph extends TrackerCommand
{
    /**
     * The GraphComposer object.
     *
     * @var    GraphComposer
     * @since  1.0
     */
    protected $graph;

    /**
     * Configure the command.
     *
     * @return  void
     *
     * @since   2.0.0
     */
    protected function configure(): void
    {
        $this->setName('make:composergraph');
        $this->setDescription('Graph visualisation for your project\'s composer.json and its dependencies.');
        $this->addOption('file', 'f', InputOption::VALUE_REQUIRED, 'Write output to a file.');
        $this->addOption('format', '', InputOption::VALUE_REQUIRED, 'The image type.');
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
        $ioStyle->title('Make Composer graph');

        $ioStyle->error('Currently Composer graphs dependencies are not compatible with Symfony 6. So this command
        is disabled. At that point we will re-add clue/graph-composer as a dependency.');

        return Command::FAILURE;

        $this->graph = new GraphComposer(JPATH_ROOT);

        $filePath = $input->getOption('file');
        $format   = $input->getOption('format');

        if ($filePath) {
            $this->export($filePath, $format);
        } else {
            $this->show($format);
        }

        return Command::SUCCESS;
    }

    /**
     * Generate the graph and open it in the default system application.
     *
     * @param   string  $format  The image type (defaults to svg).
     *
     * @return  $this
     *
     * @since   1.0
     */
    private function show($format = '')
    {
        $this->setFormat($format);

        $this->graph->displayGraph();

        return $this->out('The graph has been created.');
    }

    /**
     * Generate the graph and export it to a file.
     *
     * @param   string  $filePath  Path to the file to receive the export or a directory.
     * @param   string  $format    The image type (defaults to svg).
     *
     * @return  $this
     *
     * @since   1.0
     */
    private function export($filePath, $format = '')
    {
        if (is_dir($filePath)) {
            // If a directory is given, use a default file name.
            $filePath = rtrim($filePath, '/') . '/graph-composer.' . ($format ?: 'svg');
        }

        $filename = basename($filePath);

        $pos = strrpos($filename, '.');

        if ($pos !== false && isset($filename[$pos + 1])) {
            // Extension found and not empty.
            $this->setFormat(substr($filename, $pos + 1));
        }

        // The "--format" option from the command line overrides the file extension.
        $this->setFormat($format);

        $path = $this->graph->getImagePath();

        rename($path, $filePath);

        return $this->out(\sprintf('The file has been written to <fg=green>%s</fg=green>', realpath($filePath)));
    }

    /**
     * Set the image type for the graph.
     *
     * @param   string  $format  The image type.
     *
     * @return  $this
     *
     * @since   1.0
     */
    private function setFormat($format = '')
    {
        if ($format) {
            $this->graph->setFormat($format);
            $this->debugOut(\sprintf('Format has been set to <b>%s</b>', $format));
        }

        return $this;
    }
}
