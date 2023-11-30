<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Command\Update;

use Joomla\Github\Github;
use JTracker\Command\TrackerCommand;
use Symfony\Component\Console\Input\InputOption;

/**
 * Command package for updating selected resources
 *
 * @since  1.0
 */
abstract class Update extends TrackerCommand
{
	/**
	 * Joomla! Github object
	 *
	 * @var    Github
	 * @since  1.0
	 */
	protected $github;

	/**
	 * Common Option for project filtering.
	 *
	 * @return  void
	 *
	 * @since   2.0.0
	 */
	protected function addProjectOption(): void
	{
		$this->addOption('project', 'p', InputOption::VALUE_REQUIRED, 'Process the project with the given ID.');
	}

	/**
	 * Setup the Github object.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	protected function setupGitHub()
	{
		$this->github = $this->getContainer()->get('gitHub');

		return $this;
	}
}
