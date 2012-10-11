#!/usr/bin/env php
<?php
/**
 * User: elkuku
 * Date: 08.10.12
 * Time: 20:30
 *
 * @todo improveme =;)
 */

'cli' == PHP_SAPI || die('This script must be run from the command line');

// We are a valid entry point.
const _JEXEC = 1;

error_reporting(-1);

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

// Get the framework.
require_once JPATH_LIBRARIES . '/import.php';

/**
 * Simple Installer.
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
	 * @throws UnexpectedValueException
	 * @throws InstallerAbortException
	 *
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

			$this->out('WARNING: A database has been found. Do you want to reinstall ? [y]es / [n]o :', false);

			$resp = trim($this->in());

			if ('yes' != $resp && 'y' != $resp)
				throw new InstallerAbortException;

			// Remove existing tables

			$this->out('Remove existing tables...', false);

			foreach ($tables as $table)
			{
				$db->setQuery('DROP TABLE ' . $table)->execute();

				$this->out('.', false);
			}

			$this->out('ok');
		}
		catch (RuntimeException $e)
		{
			// Note: we may end up here if the db has not been found *OR* if the db server is down.
			// @todo check $e->getCode()

			$this->out('No database found.');// ? really..

			$this->out('Creating the database...', false);

			$db->setQuery('CREATE DATABASE ' . JFactory::getConfig()->get('db'))->execute();

			$db->select(JFactory::getConfig()->get('db'));

			$this->out('ok');
		}

		// Install.
		$sql = file_get_contents(JPATH_ROOT . '/sql/mysql.sql');

		if (false == $sql)
			throw new UnexpectedValueException('SQL file not found.');

		$this->out('Creating tables from file /sql/mysql.sql', false);

		foreach ($db->splitSql($sql) as $query)
		{
			$q = trim($db->replacePrefix($query));

			if ('' == trim($q))
				continue;

			$db->setQuery($q)->execute();

			$this->out('.', false);
		}

		$this->out('ok');
		
		$this->out('Do you want to create an admin user? [y]es / [n]o :', false);	

		$resp = trim($this->in());

		if ('yes' == $resp || 'y' == $resp)
		{
			$this->out('Enter username: ', false);
			$username = trim($this->in());
			$this->out('Enter password: ', false);
			$password = trim($this->in());
			$salt = JUserHelper::genRandomPassword(32);
			$crypt = JUserHelper::getCryptedPassword($password, $salt);

			$query = $db->getQuery(true);	
			$query->insert('#__users');
			$query->set('name = "Super User"');
			$query->set('username = '. $db->quote($username));
			$query->set('password = '. $db->quote($crypt . ':' . $salt));
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
			$query->set('user_id = '.  $db->quote($userId) );
			$query->set('group_id = 8');
			$db->setQuery($query)->execute();
			$this->out('User created');	
		}

		$this->out('Do you want to import sample Github issues? [y]es / [n]o :', false);	

		$resp = trim($this->in());

		if ('yes' == $resp || 'y' == $resp)
		{
			$sql = file_get_contents(JPATH_ROOT . '/sql/sampledata.sql');

			if (false == $sql)
				throw new UnexpectedValueException('SQL file not found.');

			$this->out('Importing sample Github issues from /sql/sampledata.sql', false);

			foreach ($db->splitSql($sql) as $query)
			{
				$q = trim($db->replacePrefix($query));

				if ('' == trim($q))
					continue;

				$db->setQuery($q)->execute();

				$this->out('.', false);
			}

			$this->out('ok');	
		}	
		
		$this->out()
			 ->out(sprintf('%s installer has terminated successfully.', $this->appName));
	}

	/**
	 * Output a nicely formatted title for the application.
	 *
	 * @param string $title    The title to display.
	 * @param string $subTitle A subtitle
	 * @param int    $width    Total width in chars
	 *
	 * @return InstallerApplication
	 */
	protected function outputTitle($title, $subTitle = '', $width = 40)
	{
		$this->out(str_repeat('-', $width));
		$this->out(str_repeat(' ', $width / 2 - (strlen($title) / 2)) . $title);

		if ($subTitle)
			$this->out(str_repeat(' ', $width / 2 - (strlen($subTitle) / 2)) . $subTitle);

		$this->out(str_repeat('-', $width))
			->out();

		return $this;
	}
}

/**
 * Exception class
 * @todo move
 */
class InstallerAbortException extends Exception{}

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
