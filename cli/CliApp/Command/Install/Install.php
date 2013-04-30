<?php
/**
 * @package     JTracker
 * @subpackage  CLI
 *
 * @copyright   Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CliApp\Command\Install;

use CliApp\Application\TrackerApplication;
use CliApp\Command\TrackerCommand;
use CliApp\Command\TrackerCommandOption;
use CliApp\Exception\AbortException;

/**
 * Class Install.
 *
 * @since  1.0
 */
class Install extends TrackerCommand
{
	/**
	 * Constructor.
	 *
	 * @param   TrackerApplication  $application  The application object.
	 */
	public function __construct(TrackerApplication $application)
	{
		parent::__construct($application);

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
	 * @throws \CliApp\Exception\AbortException
	 * @throws \Exception|\RuntimeException
	 * @throws \RuntimeException
	 * @throws \UnexpectedValueException
	 * @return void
	 */
	public function execute()
	{
		$this->application->outputTitle('Installer');

		$db = $this->application->getDatabase();

		try
		{
			// Check if the database "exists"
			$tables = $db->getTableList();

			if (!$this->application->input->get('reinstall'))
			{
				$this->out('WARNING: A database has been found !!')
					->out('Do you want to reinstall ? [y]es / [[n]]o :', false);

				$in = trim($this->application->in());

				if ('yes' != $in && 'y' != $in)
				{
					throw new AbortException;
				}
			}

			// Remove existing tables
			$this->out('Removing existing tables...', false);

			// Foreign key constraint fails fix
			$db->setQuery('SET FOREIGN_KEY_CHECKS=0')
				->execute();

			// First, need to drop the tables with FKs in specific order
			$keyTables = array(
				$db->replacePrefix('#__tracker_fields_values'),
				$db->replacePrefix('#__activity'),
				$db->replacePrefix('#__issues'),
				$db->replacePrefix('#__status')
			);

			foreach ($keyTables as $table)
			{
				$db->setQuery('DROP TABLE IF EXISTS ' . $table)
					->execute();
				$this->out('.', false);
			}

			foreach ($tables as $table)
			{
				if ('sqlite_sequence' == $table)
				{
					continue;
				}

				$db->setQuery('DROP TABLE IF EXISTS ' . $table)
					->execute();
				$this->out('.', false);
			}

			$db->setQuery('SET FOREIGN_KEY_CHECKS=1')
				->execute();

			$this->out('ok');
		}
		catch (\RuntimeException $e)
		{
			// Check if the message is "Could not connect to database."  Odds are, this means the DB isn't there or the server is down.
			if (strpos($e->getMessage(), 'Could not connect to database.') !== false)
			{
				// ? really..
				$this->out('No database found.');

				$this->out('Creating the database...', false);

				$db->setQuery('CREATE DATABASE ' . $db->quoteName($this->application->get('database.name')))
					->execute();

				$db->select($this->application->get('database.name'));

				$this->out('ok');
			}
			else
			{
				throw $e;
			}
		}

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

		foreach ($db->splitSql($sql) as $query)
		{
			$q = trim($db->replacePrefix($query));

			if ('' == trim($q))
			{
				continue;
			}

			$db->setQuery($q);

			try
			{
				$db->execute();
			}
			catch (\RuntimeException $e)
			{
				throw new \RuntimeException($e->getMessage());
			}

			$this->out('.', false);
		}

		$this->out('ok');

		/* @var \DirectoryIterator $fileInfo */
		/*
		foreach (new DirectoryIterator(JPATH_ROOT . '/sql') as $fileInfo)
		{
			if ($fileInfo->isDot())
			{
				continue;
			}

			$fileName = $fileInfo->getFilename();

			if ('index.html' == $fileName
				|| $dbType . '.sql' == $fileName)
			{
				continue;
			}

			...// Process optional SQL files

			$this->out(sprintf('Process: %s? [[y]]es / [n]o :', $fileName), false);

			$in = trim($this->in());

			if ('no' == $in || 'n' == $in)
			{
				continue;
			}

			$sql = file_get_contents(JPATH_ROOT . '/sql/' . $fileName);

			if (false == $sql)
			{
				throw new UnexpectedValueException('SQL file not found.');
			}

			$this->out(sprintf('Processing %s', $fileName), false);

			foreach ($db->splitSql($sql) as $query)
			{
				$q = trim($db->replacePrefix($query));

				if ('' == trim($q))
				{
					continue;
				}

				$db->setQuery($q)->execute();

				$this->out('.', false);
			}

			$this->out('ok');
		}
		*/

		// @todo disabled
		// $this->out('Do you want to create an admin user? [[y]]es / [n]o :', false);

		// $in = trim($this->in());
		$in = 'no';

		if ('no' != $in && 'n' != $in)
		{
			$this->out('Username [[admin]]: ', false);
			$username = trim($this->application->in());
			$username = $username ? : 'admin';

			$this->out('Password [[test]]: ', false);
			$password = trim($this->application->in());
			$password = $password ? : 'test';

			// JUserHelper::genRandomPassword(32);
			$salt  = 'a';

			// JUserHelper::getCryptedPassword($password, $salt);
			$crypt = 'b';

			$query = $db->getQuery(true);

			$data = array(
				$db->quoteName('name')          => $db->quote('Super User'),
				$db->quoteName('username')      => $db->quote($username),
				$db->quoteName('password')      => $db->quote($crypt . ':' . $salt),
				$db->quoteName('email')         => $db->quote('test@localhost.test'),
				$db->quoteName('block')         => 0,
				$db->quoteName('sendEmail')     => 1,
				$db->quoteName('registerDate')  => $db->quote('0000-00-00 00:00:00'),
				$db->quoteName('lastvisitDate') => $db->quote('0000-00-00 00:00:00'),
				$db->quoteName('activation')    => 0,
				$db->quoteName('params')        => $db->quote('{}'),
				$db->quoteName('lastResetTime') => $db->quote('0000-00-00 00:00:00'),
				$db->quoteName('resetCount')    => 0
			);

			$db->setQuery(
				$query->insert('#__users')
					->columns(array_keys($data))
					->values(implode(',', $data))
			)->execute();

			$userId = $db->insertid();

			$data = array(
				$db->quoteName('user_id')  => $userId,
				$db->quoteName('group_id') => 8
			);

			$db->setQuery(
				$query->clear()
					->insert('#__user_usergroup_map')
					->columns(array_keys($data))
					->values(implode(',', $data))
			)->execute();

			$this->out('User created.');
		}

		$this->out()
			->out('Installer has terminated successfully.');
	}
}
