<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Application\Command\Install;

use Application\Command\TrackerCommand;
use Application\Command\TrackerCommandOption;
use Application\Exception\AbortException;

/**
 * Class to install the tracker application.
 *
 * @since  1.0
 */
class Install extends TrackerCommand
{
	/**
	 * Database driver object.
	 *
	 * @var    \Joomla\Database\DatabaseDriver
	 * @since  1.0
	 */
	private $db;

	/**
	 * Constructor.
	 *
	 * @since   1.0
	 */
	public function __construct()
	{
		$this->description = 'Install the application.';

		$this->addOption(
			new TrackerCommandOption(
				'reinstall',
				'',
				'Reinstall the application (without confirmation)'
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
	 * @throws  \UnexpectedValueException
	 */
	public function execute()
	{
		$this->getApplication()->outputTitle('Installer');

		$this->db = $this->getContainer()->get('db');

		try
		{
			// Check if the database "exists"
			$tables = $this->db->getTableList();

			if (!$this->getOption('reinstall'))
			{
				$this->out()
					->out('<fg=black;bg=yellow>WARNING: A database has been found!</fg=black;bg=yellow>')
					->out()
					->out('Do you want to reinstall?')
					->out()
					->out('1) Yes')
					->out('2) No')
					->out()
					->out('<question>Select:</question>', false);

				$in = trim($this->getApplication()->in());

				if ((int) $in != 1)
				{
					throw new AbortException;
				}
			}

			$this->cleanDatabase($tables)
				->outOK();
		}
		catch (\RuntimeException $e)
		{
			// Check if the message is "Could not connect to database."  Odds are, this means the DB isn't there or the server is down.
			if (strpos($e->getMessage(), 'Could not connect to database.') !== false)
			{
				// ? really..
				$this
					->out('No database found.')
					->out('Creating the database...', false);

				$this->db->setQuery('CREATE DATABASE ' . $this->db->quoteName($this->getApplication()->get('database.name')))
					->execute();

				$this->db->select($this->getApplication()->get('database.name'));

				$this->outOK();
			}
			else
			{
				throw $e;
			}
		}

		// Perform the installation
		$this
			->processSql()
			->out()
			->out('<ok>Installation has been completed successfully.</ok>');
	}

	/**
	 * Cleanup the database.
	 *
	 * @param   array  $tables  Tables to remove.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	private function cleanDatabase(array $tables)
	{
		$this->out('Removing existing tables...', false);

		// Foreign key constraint fails fix
		$this->db->setQuery('SET FOREIGN_KEY_CHECKS=0')
			->execute();

		foreach ($tables as $table)
		{
			if ($table == 'sqlite_sequence')
			{
				continue;
			}

			$this->db->dropTable($table, true);
			$this->out('.', false);
		}

		$this->db->setQuery('SET FOREIGN_KEY_CHECKS=1')
			->execute();

		return $this;
	}

	/**
	 * Process the main SQL file.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 * @throws  \UnexpectedValueException
	 */
	private function processSql()
	{
		// Install.
		$dbType = $this->getApplication()->get('database.driver');

		if ($dbType == 'mysqli')
		{
			$dbType = 'mysql';
		}

		$fName = JPATH_ROOT . '/etc/' . $dbType . '.sql';

		if (file_exists($fName) === false)
		{
			throw new \UnexpectedValueException(sprintf('Install SQL file for %s not found.', $dbType));
		}

		$sql = file_get_contents($fName);

		if ($sql === false)
		{
			throw new \UnexpectedValueException('SQL file corrupted.');
		}

		$this->out(sprintf('Creating tables from file %s', realpath($fName)), false);

		foreach ($this->db->splitSql($sql) as $query)
		{
			$q = trim($this->db->replacePrefix($query));

			if (trim($q) == '')
			{
				continue;
			}

			$this->db->setQuery($q)
				->execute();

			$this->out('.', false);
		}

		$this->outOK();

		return $this;
	}
}
