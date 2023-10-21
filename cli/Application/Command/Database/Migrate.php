<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Application\Command\Database;

use Application\Command\TrackerCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * CLI command for migrating the database
 *
 * @since  1.0
 */
class Migrate extends TrackerCommand
{
	/**
	 * Configure the command.
	 *
	 * @return  void
	 *
	 * @since   2.0.0
	 */
	protected function configure(): void
	{
		$this->setName('database:migrate');
		$this->setDescription('Migrate the database schema to a newer version.');
		$this->addOption('version', null, InputOption::VALUE_OPTIONAL, 'Apply a specific database version.');
	}

	/**
	 * Execute the command.
	 *
	 * @param   InputInterface   $input   The input to inject into the command.
	 * @param   OutputInterface  $output  The output to inject into the command.
	 *
	 * @return  integer
	 *
	 * @since   1.0
	 */
	protected function doExecute(InputInterface $input, OutputInterface $output): int
	{
		$ioStyle = new SymfonyStyle($input, $output);
		$ioStyle->title('Database Migrations: Migrate');

		/** @var \JTracker\Database\Migrations $migrations */
		$migrations = $this->getContainer()->get('db.migrations');

		// If a version is given, we are only executing that migration
		$version = $input->getOption('version');

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
				'Error migrating database: %s',
				$exception->getMessage()
			);

			$ioStyle->error($message);
		}

		$this->getLogger()->info('Database migrated to latest version.');
		$ioStyle->success('Database migrated to latest version.');

		return 0;
	}
}
