<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Application\Command\Update;

use BabDev\Transifex\Transifex;

use Application\Command\TrackerCommand;
use Application\Command\TrackerCommandOption;

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
	 * Transifex object
	 *
	 * @var    Transifex
	 * @since  1.0
	 */
	protected $transifex;

	/**
	 * Constructor.
	 *
	 * @since   1.0
	 */
	public function __construct()
	{
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
	 * NOTE: This command must not be executed without parameters !
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		$className = join('', array_slice(explode('\\', get_class($this)), -1));

		return $this->displayMissingOption(strtolower($className), __DIR__);
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

	/**
	 * Setup the Transifex object.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	protected function setupTransifex()
	{
		$this->transifex = $this->getContainer()->get('transifex');

		return $this;
	}
}
