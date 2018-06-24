<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Application\Command\Database;

use Application\Command\TrackerCommandOption;

/**
 * CLI command for migrating the database
 *
 * @since  1.0
 */
class Migrate extends Database
{
	/**
	 * Constructor.
	 *
	 * @since   1.0
	 */
	public function __construct()
	{
		parent::__construct();

		$this->description = g11n3t('Migrate the database schema to a newer version.');

		$this->addOption(
			new TrackerCommandOption(
				'version', '',
				g11n3t('Apply a specific database version.')
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
		$this->getApplication()->outputTitle(g11n3t('Database Migrations: Migrate'));

		/** @var \JTracker\Database\Migrations $migrations */
		$migrations = $this->getContainer()->get('db.migrations');

		// If a version is given, we are only executing that migration
		$version = $this->getOption('version');

		try
		{
			$migrations->migrateDatabase($version);
		}
		catch (\Exception $exception)
		{
			$this->getLogger()->critical(
				'Error migrating database',
				['exception' => $exception]
			);

			$message = sprintf(
				g11n3t('Error migrating database: %s'),
				$exception->getMessage()
			);

			$this->getApplication()->out("<error>$message</error>");
		}

		$this->getLogger()->info('Database migrated to latest version.');

		$this->getApplication()->out('<ok>' . g11n3t('Database migrated to latest version.') . '</ok>');
	}
}
