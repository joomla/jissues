<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Application\Command\Update;

use Application\Command\Database\Migrate;
use Application\Command\Make\Repoinfo;
use Application\Command\TrackerCommandOption;
use Application\Exception\AbortException;

/**
 * Class for synchronizing a server with the primary git repository
 *
 * @since  1.0
 */
class Server extends Update
{
	/**
	 * Constructor.
	 *
	 * @since   1.0
	 */
	public function __construct()
	{
		parent::__construct();

		$this->description = g11n3t('Updates the local installation to either a specified version or latest git HEAD for the active branch');

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
	 * @since   1.0
	 * @throws  AbortException
	 * @throws  \RuntimeException
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

			$message = sprintf('Update to version %s successful', $version);
		}
		else
		{
			// Perform a git pull on the active branch
			$this->execCommand('cd ' . JPATH_ROOT . ' && git pull 2>&1');

			$message ='Git update Finished';
		}

		// Update the Composer installation
		$this->execCommand('cd ' . JPATH_ROOT . ' && composer install --no-dev --optimize-autoloader 2>&1');

		// Execute the database migrations (if any) for this version
		(new Migrate)
			->setContainer($this->getContainer())
			->execute();

		$this->logOut($message);

		(new Repoinfo)
			->setContainer($this->getContainer())
			->execute();

		$this->logOut('Update Finished');
	}
}
