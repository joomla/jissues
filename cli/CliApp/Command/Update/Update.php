<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace CliApp\Command\Update;

use CliApp\Command\TrackerCommand;
use CliApp\Command\TrackerCommandOption;

use Joomla\Github\Github;

/**
 * Command package for updating selected resources
 *
 * @since  1.0
 */
class Update extends TrackerCommand
{
	/**
	 * The command "description" used for help texts.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $description = 'Used to update resources';

	/**
	 * Joomla! Github object
	 *
	 * @var    Github
	 * @since  1.0
	 */
	protected $github;

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
					'project', 'p',
					'Process the project with the given ID.'
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
		$this->getApplication()->outputTitle('Update');

		$errorTitle = 'Please use one of the following:';

		$this->out('<error>                                    </error>');
		$this->out('<error>  ' . $errorTitle . '  </error>');

		foreach (Folder::files(__DIR__) as $file)
		{
			$cmd = strtolower(substr($file, 0, strlen($file) - 4));

			if ('update' == $cmd)
			{
				continue;
			}

			$this->out('<error>  update ' . $cmd . str_repeat(' ', strlen($errorTitle) - strlen($cmd) - 3) . '</error>');
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
