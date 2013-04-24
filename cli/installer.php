#!/usr/bin/env php
<?php
/**
 * @package     JTracker
 * @subpackage  CLI
 *
 * @copyright   Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

include '../vendor/autoload.php';

// @todo used by JFactory::getConfig() and getDbo()
define('JPATH_FRAMEWORK', 'dooo');

use Joomla\Application\AbstractCliApplication;
use Joomla\Factory;

// @todo Parent App should load the config
include '../etc/configuration.php';

// Configure error reporting to maximum for CLI output.
error_reporting(-1);
ini_set('display_errors', 1);

/**
 * Simple Installer.
 *
 * @package     JTracker
 * @subpackage  CLI
 * @since       1.0
 */
class InstallerApplication extends AbstractCliApplication
{
	/**
	 * @var string
	 */
	protected $appName = 'JTracker';

	/**
	 * @var string
	 */
	protected $appVersion = '2.0';

	/**
	 * Method to run the application routines.
	 *
	 * @throws RuntimeException
	 * @throws Exception|RuntimeException
	 * @throws InstallerAbortException
	 * @throws UnexpectedValueException
	 *
	 * @return  void
	 */
	protected function doExecute()
	{
		$this->outputTitle($this->appName . ' Installer', $this->appVersion);

		$config = Factory::getConfig();
		$db     = Factory::getDbo();

		try
		{
			// Check if the database "exists"
			$tables = $db->getTableList();

			if (!$this->input->get('reinstall'))
			{
				$this->out('WARNING: A database has been found !!')
					->out('Do you want to reinstall ? [y]es / [[n]]o :', false);

				$in = trim($this->in());

				if ('yes' != $in && 'y' != $in)
				{
					throw new InstallerAbortException;
				}
			}

			// Remove existing tables

			$this->out('Removing existing tables...', false);

			// First, need to drop the tables with FKs in specific order
			$keyTables = array(
				$db->replacePrefix('#__tracker_fields_values'),
				$db->replacePrefix('#__activity'),
				$db->replacePrefix('#__issues'),
				$db->replacePrefix('#__status')
			);

			foreach ($keyTables as $table)
			{
				$db->setQuery('DROP TABLE IF EXISTS ' . $table)->execute();
				$this->out('.', false);
			}

			foreach ($tables as $table)
			{
				if ('sqlite_sequence' == $table)
				{
					continue;
				}

				$db->setQuery('DROP TABLE IF EXISTS ' . $table)->execute();
				$this->out('.', false);
			}

			$this->out('ok');
		}
		catch (RuntimeException $e)
		{
			// Check if the message is "Could not connect to database."  Odds are, this means the DB isn't there or the server is down.
			if (strpos($e->getMessage(), 'Could not connect to database.') !== false)
			{
				// ? really..
				$this->out('No database found.');

				$this->out('Creating the database...', false);

				$dbOptions          = new stdClass;
				$dbOptions->db_name = $config->get('db');
				$dbOptions->db_user = $config->get('user');

				$db->setQuery('CREATE DATABASE ' . $db->quoteName($dbOptions->db_name))
					->execute();

				$db->select($config->get('db'));

				$this->out('ok');
			}
			else
			{
				throw $e;
			}
		}

		// Install.

		$dbType = $config->get('dbtype');

		if ('mysqli' == $dbType)
		{
			$dbType = 'mysql';
		}

		$fName = '../etc/' . $dbType . '.sql';

		if (false == file_exists($fName))
		{
			throw new UnexpectedValueException(sprintf('Install SQL file for %s not found.', $dbType));
		}

		$sql = file_get_contents($fName);

		if (false == $sql)
		{
			throw new UnexpectedValueException('SQL file corrupted.');
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
			catch (RuntimeException $e)
			{
				throw new RuntimeException($e->getMessage());
			}

			$this->out('.', false);
		}

		$this->out('ok');

		/* @var DirectoryIterator $fileInfo */
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

			// Process optional SQL files
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

		$in = 'no'; //trim($this->in());

		if ('no' != $in && 'n' != $in)
		{
			$this->out('Username [[admin]]: ', false);
			$username = trim($this->in());
			$username = $username ? : 'admin';

			$this->out('Password [[test]]: ', false);
			$password = trim($this->in());
			$password = $password ? : 'test';

			$salt  = JUserHelper::genRandomPassword(32);
			$crypt = JUserHelper::getCryptedPassword($password, $salt);

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
			->out(sprintf('%s installer has terminated successfully.', $this->appName));
	}

	/**
	 * Output a nicely formatted title for the application.
	 *
	 * @param   string  $title     The title to display.
	 * @param   string  $subTitle  A subtitle
	 * @param   int     $width     Total width in chars
	 *
	 * @return InstallerApplication
	 */
	protected function outputTitle($title, $subTitle = '', $width = 40)
	{
		$this->out(str_repeat('-', $width));
		$this->out(str_repeat(' ', $width / 2 - (strlen($title) / 2)) . $title);

		if ($subTitle)
		{
			$this->out(str_repeat(' ', $width / 2 - (strlen($subTitle) / 2)) . $subTitle);
		}

		$this->out(str_repeat('-', $width))
			->out();

		return $this;
	}
}

/**
 * Exception class
 *
 * @todo        move
 *
 * @package     JTracker
 * @subpackage  CLI
 * @since       1.0
 */
class InstallerAbortException extends Exception
{
}

/*
 * Main
 */
try
{
	$app = new InstallerApplication;
	$app->execute();
}
catch (InstallerAbortException $e)
{
	echo 'Installation aborted.' . "\n";

	exit(0);
}
catch (Exception $e)
{
	echo $e->getMessage() . "\n\n";

	echo $e->getTraceAsString();

	exit($e->getCode() ? : 1);
}
