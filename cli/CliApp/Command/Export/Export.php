<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace CliApp\Command\Export;

use CliApp\Command\TrackerCommand;

use CliApp\Command\TrackerCommandOption;

use Joomla\Filesystem\Folder;

/**
 * Base class for backup jobs.
 *
 * @since  1.0
 */
class Export extends TrackerCommand
{
	/**
	 * The directory to receive the export.
	 *
	 * @var string
	 * @since  1.0
	 */
	protected $exportDir = '';

	/**
	 * Constructor.
	 *
	 * @since   1.0
	 */
	public function __construct()
	{
		parent::__construct();

		$this->description = 'Export <cmd><langfiles></cmd>.';

		$this->addOption(
			new TrackerCommandOption('outputdir', 'o',
				'The directory that should receive the export.')
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
		$this->getApplication()->outputTitle('Export');

		$errorTitle = 'Please use one of the following:';
		$command = 'export';

		$this->out('<error>                                    </error>');
		$this->out('<error>  ' . $errorTitle . '  </error>');

		foreach (Folder::files(__DIR__) as $file)
		{
			$cmd = strtolower(substr($file, 0, strlen($file) - 4));

			if ($command == $cmd)
			{
				// Exclude the base class
				continue;
			}

			$this->out('<error>  ' . $command . ' ' . $cmd
				. str_repeat(' ', strlen($errorTitle) - strlen($cmd) - strlen($command) + 1)
				. '</error>');
		}

		$this->out('<error>                                    </error>');
	}

	/**
	 * Set up the environment to run the command.
	 *
	 * @throws \RuntimeException
	 * @return  $this
	 *
	 * @since   1.0
	 */
	protected function setup()
	{
		$this->exportDir = $this->getApplication()->input->getPath('output', $this->getApplication()->input->getPath('o'));

		if (!$this->exportDir)
		{
			throw new \RuntimeException("Please specify an output directory using 'outputdir' ('o')");
		}

		if (false == is_dir($this->exportDir))
		{
			throw new \RuntimeException("The output directory does not exist.");
		}

		return $this;
	}
}
