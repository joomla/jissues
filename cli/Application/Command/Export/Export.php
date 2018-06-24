<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Application\Command\Export;

use Application\Command\TrackerCommand;
use Application\Command\TrackerCommandOption;

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
		$this->description = 'Export <cmd><langfiles></cmd>.';

		$this->addOption(
			new TrackerCommandOption(
				'outputdir',
				'o',
				'The directory that should receive the export.'
			)
		);
	}

	/**
	 * Execute the command.
	 *
	 * NOTE: This command must not be executed without parameters !
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		return $this->displayMissingOption(__DIR__);
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
		$this->exportDir = $this->getOption('outputdir');

		if (!$this->exportDir)
		{
			throw new \RuntimeException("Please specify an output directory using 'outputdir' ('o')");
		}

		if (false === is_dir($this->exportDir))
		{
			throw new \RuntimeException("The output directory does not exist.");
		}

		return $this;
	}
}
