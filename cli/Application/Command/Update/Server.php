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
use Application\Exception\AbortException;

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

			// Update the Composer installation
			$this->execCommand('cd ' . JPATH_ROOT . ' && composer install --no-dev --optimize-autoloader 2>&1');

			/*
			 * If a SQL diff exists for the version requested, then process it.  It is assumed that the person
			 * updating will not be skipping versions with this implementation, but if need be, that can be supported.
			 */
			$sqlFile = JPATH_CONFIGURATION . '/updates/' . $version . '.sql';

			if (file_exists($sqlFile))
			{
				$queries = file_get_contents($sqlFile);

				if ($queries === false)
				{
					throw new AbortException(
						sprintf(
							g11n3t('Could not read data from the %s SQL file, please update the database manually.'),
							$sqlFile
						)
					);
				}

				/** @var \Joomla\Database\DatabaseDriver $db */
				$db = $this->getContainer()->get('db');

				$dbQueries = $db->splitSql($queries);

				foreach ($dbQueries as $query)
				{
					try
					{
						$db->setQuery($query)->execute();
					}
					catch (\RuntimeException $e)
					{
						throw new AbortException(
							sprintf(
								g11n3t(
									'SQL query failed, please verify the database structure and finish the update '
									. 'manually.  The database error message is: %s'
								),
								$e->getMessage()
							)
						);
					}
				}
			}

			$this->logOut(sprintf('Update to version %s successful', $version));
		}
		else
		{
			// Perform a git pull on the active branch
			$this->execCommand('cd ' . JPATH_ROOT . ' && git pull 2>&1');

			$this->logOut('Git update Finished');
		}

		(new Repoinfo)
			->setContainer($this->getContainer())
			->execute();

		$this->logOut('Update Finished');
	}
}
