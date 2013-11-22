<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CliApp\Command\Get;

use App\Projects\Table\ProjectsTable;

use CliApp\Command\TrackerCommand;
use CliApp\Command\TrackerCommandOption;

use Joomla\Github\Github;
use Joomla\Filesystem\Folder;

/**
 * Class for retrieving data from GitHub for selected projects
 *
 * @since  1.0
 */
class Get extends TrackerCommand
{
	/**
	 * Joomla! Github object
	 *
	 * @var    Github
	 * @since  1.0
	 */
	protected $github;

	/**
	 * Project object.
	 *
	 * @var    ProjectsTable
	 * @since  1.0
	 */
	protected $project = null;

	/**
	 * Constructor.
	 *
	 * @since   1.0
	 */
	public function __construct()
	{
		$this->description = 'Retrieve <cmd><issues></cmd>, <cmd><comments></cmd> or <cmd><avatars></cmd>.';

		$this
			->addOption(
				new TrackerCommandOption(
					'project', 'p',
					'Process the project with the given ID.'
				)
			)
			->addOption(
				new TrackerCommandOption(
					'auth', '',
					'Use GitHub credentials from configuration for authentication.'
				)
			)
			->addOption(
				new TrackerCommandOption(
					'noprogress', '',
					'Don\'t use a progress bar.'
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
		$this->getApplication()->outputTitle('Get');

		$errorTitle = 'Please use one of the following:';

		$this->out('<error>                                    </error>');
		$this->out('<error>  ' . $errorTitle . '  </error>');

		foreach (Folder::files(__DIR__) as $file)
		{
			$cmd = strtolower(substr($file, 0, strlen($file) - 4));

			if ('get' == $cmd)
			{
				continue;
			}

			$this->out('<error>  get ' . $cmd . str_repeat(' ', strlen($errorTitle) - strlen($cmd) - 3) . '</error>');
		}

		$this->out('<error>                                    </error>');
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
		$this->github = $this->container->get('gitHub');

		return $this;
	}
}
