#!/usr/bin/env php
<?php
/**
 * @package     JTracker
 * @subpackage  CLI
 *
 * @copyright   Copyright (C) 2012 Open Source Matters. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// We are a valid entry point.
const _JEXEC = 1;

// Load system defines
if (file_exists(dirname(__DIR__) . '/defines.php'))
{
	require_once dirname(__DIR__) . '/defines.php';
}

if (!defined('_JDEFINES'))
{
	define('JPATH_BASE', dirname(__DIR__));
	require_once JPATH_BASE . '/includes/defines.php';
}

// Bootstrap the Joomla Platform.
require_once JPATH_LIBRARIES . '/import.legacy.php';

// Bootstrap the CMS libraries.
require_once JPATH_LIBRARIES . '/cms.php';

// Bootstrap the Tracker application libraries.
require_once JPATH_LIBRARIES . '/tracker.php';

// Configure error reporting to maximum for CLI output.
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * Simple Installer.
 *
 * @package     JTracker
 * @subpackage  CLI
 * @since       1.0
 */
class InstallerApplication extends JApplicationCli
{
	/**
	 * @var string
	 */
	protected $appName = 'JTracker';

	/**
	 * Method to run the application routines.
	 *
	 * @throws RuntimeException
	 * @throws InstallerAbortException
	 * @throws UnexpectedValueException
	 * @return  void
	 */
	protected function doExecute()
	{
		$this->outputTitle($this->appName . ' Installer', '(C)');

		$db = JFactory::getDbo();

		try
		{
			// Check if the database "exists"
			$tables = $db->getTableList();

			$this->out('WARNING: A database has been found !!')->out('Do you want to reinstall ? [y]es / [[n]]o :', false);

			$in = trim($this->in());

			if ('yes' != $in && 'y' != $in)
			{
				throw new InstallerAbortException;
			}

			// Remove existing tables

			$this->out('Removing existing tables...', false);

			// First, need to drop the tables with FKs in specific order
			$keyTables = array($db->replacePrefix('#__tracker_fields_values'), $db->replacePrefix('#__issue_comments'), $db->replacePrefix('#__issues'), $db->replacePrefix('#__status'));
			foreach ($keyTables as $table)
			{
				$db->setQuery('DROP TABLE IF EXISTS ' . $table)->execute();
				$this->out('.', false);
			}

			foreach ($tables as $table)
			{
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
				$dbOptions->db_name = $this->config->get('db');
				$dbOptions->db_user = $this->config->get('user');

				$db->createDatabase($dbOptions);
				$db->select($this->config->get('db'));

				$this->out('ok');
			}
		}

		// Install.

		$dbType = $this->config->get('dbtype');

		if ('mysqli' == $dbType)
		{
			$dbType = 'mysql';
		}

		if (false == file_exists(JPATH_ROOT . '/sql/' . $dbType . '.sql'))
		{
			throw new UnexpectedValueException(sprintf('Install SQL file for %s not found.', $dbType));
		}

		$sql = file_get_contents(JPATH_ROOT . '/sql/' . $dbType . '.sql');

		if (false == $sql)
		{
			throw new UnexpectedValueException('SQL file not found.');
		}

		$this->out('Creating tables from file /sql/' . $dbType . '.sql', false);

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

		$this->out('Do you want to create an admin user? [[y]]es / [n]o :', false);

		$in = trim($this->in());

		if ('no' != $in && 'n' != $in)
		{
			$this->out('Enter username [[admin]]: ', false);
			$username = trim($this->in());
			$username = $username ? : 'admin';

			$this->out('Enter password [[test]]: ', false);
			$password = trim($this->in());
			$password = $password ? : 'test';

			$salt  = JUserHelper::genRandomPassword(32);
			$crypt = JUserHelper::getCryptedPassword($password, $salt);

			$query = $db->getQuery(true);
			$query->insert('#__users');
			$query->set('name = "Super User"');
			$query->set('username = ' . $db->quote($username));
			$query->set('password = ' . $db->quote($crypt . ':' . $salt));
			$query->set('email = "test@localhost.test"');
			$query->set('block = 0');
			$query->set('sendEmail = 1');
			$query->set('registerDate = "0000-00-00 00:00:00"');
			$query->set('lastvisitDate = "0000-00-00 00:00:00"');
			$query->set('activation = 0');
			$query->set('params = "{}"');
			$query->set('lastResetTime = "0000-00-00 00:00:00"');
			$query->set('resetCount = 0');

			$db->setQuery($query)->execute();
			$userId = $db->insertid();

			$query->clear();
			$query->insert('#__user_usergroup_map');
			$query->set('user_id = ' . $db->quote($userId));
			$query->set('group_id = 8');
			$db->setQuery($query)->execute();
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
 * @todo move
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
	JApplicationCli::getInstance('InstallerApplication')
		->execute();
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
