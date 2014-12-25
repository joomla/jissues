<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Application\Command\Update;

use Application\Command\Make\Repoinfo;
use Application\Command\TrackerCommandOption;

/**
 * Class for synchronizing a server with the primary git repository
 *
 * @since  1.0
 */
class Server extends Update
{
	/**
	 * The command "description" used for help texts.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $description = 'Updates the local installation to either a specified version or latest HEAD for the active branch';

	/**
	 * Constructor.
	 *
	 * @since   1.0
	 */
	public function __construct()
	{
		$this->addOption(
			new TrackerCommandOption(
				'version', 'v',
				'An optional version number to update to.'
			)
		);
	}

	/**
	 * Execute the command.
	 *
	 * @return  void
	 *
	 * @throws \RuntimeException
	 * @since   1.0
	 */
	public function execute()
	{
		$this->getApplication()->outputTitle('Update Server');

		$this->logOut('Beginning git update');

		if ($this->getApplication()->input->getBool('version', false))
		{
			$version = $this->getApplication()->input->getString('version');

			// Fetch from remote sources and checkout the specified version tag
			$this->execCommand('cd ' . JPATH_ROOT . ' && git fetch && git checkout ' . $version . ' 2>&1');

			// TODO - Implement automated support for SQL updates
		}
		else
		{
			// Perform a git pull on the active branch
			$this->execCommand('cd ' . JPATH_ROOT . ' && git pull 2>&1');
		}

		$this->logOut('Git update Finished');

		(new Repoinfo)
			->setContainer($this->getContainer())
			->execute();

		$this->logOut('Update Finished');
	}
}
