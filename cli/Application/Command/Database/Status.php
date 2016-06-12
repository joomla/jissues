<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Application\Command\Database;

use Application\Command\TrackerCommand;

/**
 * CLI command for checking the database migration status
 *
 * @since  1.0
 */
class Status extends TrackerCommand
{
	/**
	 * Constructor.
	 *
	 * @since   1.0
	 */
	public function __construct()
	{
		$this->description = g11n3t('Check the database migration status.');
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
		$this->getApplication()->outputTitle(g11n3t('Database Migrations: Check Status'));

		/** @var \JTracker\Database\Migrations $migrations */
		$migrations = $this->getContainer()->get('db.migrations');

		$status = $migrations->checkStatus();

		if ($status['latest'])
		{
			$this->getApplication()->out('<fg=green;options=bold>Your database is up-to-date.</fg=green;options=bold>');
		}
		else
		{
			$this->getApplication()->out(
				'<comment>'
				. sprintf(
					g11n4t(
						'Your database is not up-to-date. You are missing one migration.',
						'Your database is not up-to-date. You are missing %d migrations.',
						$status['missingMigrations']
					),
					$status['missingMigrations']
				)
				. '</comment>'
			)
				->out()
				->out('<comment>' . g11n3t(sprintf('Current Version: %1$s', $status['currentVersion'])) . '</comment>')
				->out('<comment>' . g11n3t(sprintf('Latest Version: %1$s', $status['latestVersion'])) . '</comment>')
				->out()
				->out(g11n3t(sprintf('To update, run the %1$s command.', '<question>database:migrate</question>')));
		}
	}
}
