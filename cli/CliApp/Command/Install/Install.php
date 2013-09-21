<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CliApp\Command\Install;

use CliApp\Command\TrackerCommand;
use CliApp\Command\TrackerCommandOption;
use CliApp\Exception\AbortException;

use JTracker\Container;

/**
 * Class to install the tracker application.
 *
 * @since  1.0
 */
class Install extends TrackerCommand
{
	/**
	 *  @var \Joomla\Database\DatabaseDriver
	 */
	private $db = null;

	/**
	 * Constructor.
	 *
	 * @since   1.0
	 */
	public function __construct()
	{
		parent::__construct();

		$this->db = Container::getInstance()->get('db');

		$this->description = 'Install the application.';

		$this->addOption(
			new TrackerCommandOption(
				'reinstall', '',
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
	 * @throws  \CliApp\Exception\AbortException
	 * @throws  \RuntimeException
	 * @throws  \UnexpectedValueException
	 */
	public function execute()
	{
		$this->application->outputTitle('Installer');

		try
		{
			// Check if the database "exists"
			$tables = $this->db->getTableList();

			if (!$this->application->input->get('reinstall'))
			{
				$this->out('<fg=black;bg=yellow>WARNING: A database has been found !!</fg=black;bg=yellow>')
					->out('Do you want to reinstall ? [y]es / [[n]]o :', false);

				$in = trim($this->application->in());

				if ('yes' != $in && 'y' != $in)
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

				$this->db->setQuery('CREATE DATABASE ' . $this->db->quoteName($this->application->get('database.name')))
					->execute();

				$this->db->select($this->application->get('database.name'));

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
			->out('<ok>Installer has terminated successfully.</ok>');
	}

	/**
	 * Cleanup the database.
	 *
	 * @param   array  $tables  Tables to remove.
	 *
	 * @return $this
	 */
	private function cleanDatabase(array $tables)
	{
		$this->out('Removing existing tables...', false);

		// Foreign key constraint fails fix
		$this->db->setQuery('SET FOREIGN_KEY_CHECKS=0')
			->execute();

		foreach ($tables as $table)
		{
			if ('sqlite_sequence' == $table)
			{
				continue;
			}

			$this->db->setQuery('DROP TABLE IF EXISTS ' . $table)
				->execute();
			$this->out('.', false);
		}

		$this->db->setQuery('SET FOREIGN_KEY_CHECKS=1')
			->execute();

		return $this;
	}

	/**
	 * Process the main SQL file.
	 *
	 * @since  1.0
	 * @throws \RuntimeException
	 * @throws \UnexpectedValueException
	 * @return $this
	 */
	private function processSql()
	{
		// Install.
		$dbType = $this->application->get('database.driver');

		if ('mysqli' == $dbType)
		{
			$dbType = 'mysql';
		}

		$fName = __DIR__ . '/../../../../etc/' . $dbType . '.sql';

		if (false == file_exists($fName))
		{
			throw new \UnexpectedValueException(sprintf('Install SQL file for %s not found.', $dbType));
		}

		$sql = file_get_contents($fName);

		if (false == $sql)
		{
			throw new \UnexpectedValueException('SQL file corrupted.');
		}

		$this->out(sprintf('Creating tables from file %s', realpath($fName)), false);

		foreach ($this->db->splitSql($sql) as $query)
		{
			$q = trim($this->db->replacePrefix($query));

			if ('' == trim($q))
			{
				continue;
			}

			$this->db->setQuery($q)
				->execute();

			$this->out('.', false);
		}

		$this->outOk();

		return $this;
	}
}
