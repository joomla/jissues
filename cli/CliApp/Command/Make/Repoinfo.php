<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2013 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CliApp\Command\Make;

use CliApp\Application\CliApplication;

use JTracker\Container;

/**
 * Class for generating repository information.
 *
 * @since  1.0
 */
class Repoinfo extends Make
{
	/**
	 * Constructor.
	 *
	 * @param   CliApplication  $application  The application object.
	 *
	 * @since   1.0
	 */
	public function __construct(CliApplication $application)
	{
		$this->application = $application;
		$this->description = 'Generate repository information.';
		$this->logger      = Container::retrieve('logger');
	}

	/**
	 * Execute the command.
	 *
	 * @return  void
	 *
	 * @throws \DomainException
	 * @since   1.0
	 */
	public function execute()
	{
		$this->application->outputTitle('Generate Repoinfo');
		$this->logOut('Generating Repoinfo.');

		$currentSHA = trim(shell_exec('git describe --long --dirty --abbrev=10 --tags'));

		$path = JPATH_ROOT . '/current_SHA';

		if (false == file_put_contents($path, $currentSHA))
		{
			throw new \DomainException('Can not write to path: ' . $path);
		}

		$this->logOut(sprintf('Wrote repoinfo file to: %s', str_replace(JPATH_ROOT, 'J_ROOT', $path)))
			->out()
			->out('Finished =;)');
	}
}
