<?php
/**
 * @copyright  Copyright (C) 2013 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tracker\Components\Debug\Database;

use Joomla\Database\DatabaseDriver;
use Joomla\Tracker\Components\Debug\Format\Html\TableFormat;

/**
 * Class DatabaseDebugger.
 *
 * @since  10
 */
class DatabaseDebugger
{
	/**
	 * @var  DatabaseDriver
	 */
	private $database;

	/**
	 * Constructor.
	 *
	 * @param   DatabaseDriver  $database  The database driver.
	 */
	public function __construct(DatabaseDriver $database)
	{
		$this->database = $database;
	}

	/**
	 * Get the database prefix.
	 *
	 * @since  1.0
	 * @return string
	 */
	public function getPrefix()
	{
		return $this->database->getPrefix();
	}

	/**
	 * Get a database explain statement.
	 *
	 * @param   string  $query  The query.
	 *
	 * @since  1.0
	 * @return string
	 */
	public function getExplain($query)
	{
		$db = $this->database;

		$db->setDebug(false);

		// Run an EXPLAIN EXTENDED query on the SQL query if possible:
		$explain = '';

		$tableFormat = new TableFormat;

		if (in_array($db->name, array('mysqli','mysql', 'postgresql')))
		{
			$dbVersion56 = ( strncmp($db->name, 'mysql', 5) == 0 ) && version_compare($db->getVersion(), '5.6', '>=');

			if ((stripos($query, 'select') === 0) || ($dbVersion56 && ((stripos($query, 'delete') === 0)||(stripos($query, 'update') === 0))))
			{
				$db->setQuery('EXPLAIN ' . ($dbVersion56 ? 'EXTENDED ' : '') . $query);

				if ($db->execute())
				{
					$explain = $tableFormat->fromArray($db->loadAssocList());
				}
				else
				{
					$explain = 'Failed EXPLAIN on query: ' . htmlspecialchars($query);
				}
			}
		}

		$db->setDebug(true);

		return $explain;
	}

	/**
	 * Get a db profile.
	 *
	 * @param   string  $query  The query.
	 *
	 * @todo Not used yet
	 *
	 * @return string
	 */
	public function getProfile($query)
	{
		/* @type TrackerApplication $application */
		$application = Factory::$application;
		$db = $application->getDatabase();

		// Run a SHOW PROFILE query:
		$profile = '';

		if (false == in_array($db->name, array('mysqli','mysql')))
		{
			return sprintf('%d database is not supported yet.', $db->name);
		}

		$db->setDebug(false);

		$dbVersion5037 = (strncmp($db->name, 'mysql', 5) == 0 ) && version_compare($db->getVersion(), '5.0.37', '>=');

		if ($dbVersion5037)
		{
			// Run a SHOW PROFILE query:
			// SHOW PROFILE ALL FOR QUERY ' . (int) ($k+1));
			$db->setQuery('SHOW PROFILES');
			$profiles = $db->loadAssocList();

			if ($profiles)
			{
				foreach ($profiles as $qn)
				{
					$db->setQuery('SHOW PROFILE FOR QUERY ' . (int) ($qn['Query_ID']));
					$this->sqlShowProfileEach[(int) ($qn['Query_ID'] - 1)] = $db->loadAssocList();
				}
			}
		}

		if (in_array($db->name, array('mysqli','mysql', 'postgresql')))
		{
			$log = $db->getLog();

			foreach ($log as $k => $query)
			{
				$dbVersion56 = ( strncmp($db->name, 'mysql', 5) == 0 ) && version_compare($db->getVersion(), '5.6', '>=');

				if ((stripos($query, 'select') === 0) || ($dbVersion56 && ((stripos($query, 'delete') === 0)||(stripos($query, 'update') === 0))))
				{
					$db->setQuery('EXPLAIN ' . ($dbVersion56 ? 'EXTENDED ' : '') . $query);
					$this->explains[$k] = $db->loadAssocList();
				}
			}
		}

		if (isset($this->sqlShowProfileEach[$k]))
		{
			$profileTable = $this->sqlShowProfileEach[$k];
			$profile = $this->tableToHtml($profileTable);
		}
		else
		{
			$profile = 'No SHOW PROFILE (maybe because more than 100 queries)';
		}
	}
}
