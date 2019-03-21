<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Application\Command\Database;

/**
 * CLI command for checking the database migration status
 *
 * @since  1.0
 */
class Status extends Database
{
	/**
	 * Constructor.
	 *
	 * @since   1.0
	 */
	public function __construct()
	{
		parent::__construct();

		$this->description = 'Check the database migration status.';
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
		$this->getApplication()->outputTitle('Database Migrations: Check Status');

		/** @var \JTracker\Database\Migrations $migrations */
		$migrations = $this->getContainer()->get('db.migrations');

		$status = $migrations->checkStatus();

		if ($status['latest'])
		{
			$this->getApplication()->out('<ok>Your database is up-to-date.</ok>');
		}
		else
		{
			$this->getApplication()->out(
				sprintf(
					'<comment>Your database is not up-to-date. You are missing %d migrations.</comment>', $status['missingMigrations']
				)
			)
				->out()
				->out(sprintf('<comment>Current Version: %1$s</comment>', $status['currentVersion']))
				->out(sprintf('<comment>Latest Version: %1$s</comment>', $status['latestVersion']))
				->out()
				->out('To update, run the <question>database:migrate</question> command.');
		}
	}
}
