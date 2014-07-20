<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Application\Command\Make;

use Application\Command\TrackerCommand;
use Application\Command\TrackerCommandOption;

/**
 * Class for retrieving issues from GitHub for selected projects
 *
 * @since  1.0
 */
class Make extends TrackerCommand
{
	/**
	 * The command "description" used for help texts.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $description = 'The make engine';

	/**
	 * Joomla! Github object
	 *
	 * @var    \Joomla\Github\Github
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
		$this->addOption(
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
}
