<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Application\Command\Make;

use Application\Command\TrackerCommandOption;

use Clue\GraphComposer;

/**
 * Class for generating a graphical representation of the scripts that
 * have been installed using Composer and their dependencies.
 *
 * @since  1.0
 */
class Composergraph extends Make
{
	/**
	 * The command "description" used for help texts.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $description = 'Graph visualization for your project\'s composer.json and its dependencies.';

	/**
	 * The GraphComposer object.
	 *
	 * @var GraphComposer
	 * @since  1.0
	 */
	protected $graph = null;

	/**
	 * Constructor.
	 *
	 * @since   1.0
	 */
	public function __construct()
	{
		parent::__construct();

		$this
			->addOption(
				new TrackerCommandOption(
					'file', 'f',
					'Write output to a file.'
				)
			)
			->addOption(
				new TrackerCommandOption(
					'format', '',
					'The image type.'
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
		$this->getApplication()->outputTitle('Make Composer graph');

		$this->graph = new GraphComposer(JPATH_ROOT);

		$input = $this->getApplication()->input;

		$filePath = $input->get('file', $input->get('f', '', 'raw'), 'raw');
		$format   = $input->get('format');

		if ($filePath)
		{
			$this->export($filePath, $format);
		}
		else
		{
			$this->show($format);
		}
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

		return $this->out(sprintf('The graph has been created.'));
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
		if (is_dir($filePath))
		{
			// If a directory is given, use a default file name.
			$filePath = rtrim($filePath, '/') . '/graph-composer.' . ($format ? : 'svg');
		}

		$filename = basename($filePath);

		$pos = strrpos($filename, '.');

		if ($pos !== false && isset($filename[$pos + 1]))
		{
			// Extension found and not empty.
			$this->setFormat(substr($filename, $pos + 1));
		}

		// The "--format" option from the command line overrides the file extension.
		$this->setFormat($format);

		$path = $this->graph->getImagePath();

		rename($path, $filePath);

		return $this->out(sprintf('The file has been written to <fg=green>%s</fg=green>', realpath($filePath)));
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
		if ($format)
		{
			$this->graph->setFormat($format);
			$this->debugOut(sprintf('Format has been set to <b>%s</b>', $format));
		}

		return $this;
	}
}
