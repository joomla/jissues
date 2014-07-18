<?php
/**
 * Part of the Joomla Tracker's Debug Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Debug\Database;

use App\Debug\TrackerDebugger;

use Doctrine\DBAL\Logging\SQLLogger as DoctrineSQLLogger;

/**
 * Class Logger.
 *
 * @since  1.0
 */
class SQLLogger implements DoctrineSQLLogger
{
	/**
	 * The debugger object.
	 *
	 * @var \App\Debug\TrackerDebugger
	 *
	 * @since  1.0
	 */
	private $debugger;

	/**
	 * Constructor.
	 *
	 * @param   TrackerDebugger  $debugger  The debugger object.
	 *
	 * @since  1.0
	 */
	public function __construct(TrackerDebugger $debugger)
	{
		$this->debugger = $debugger;
	}

	/**
	 * Logs a SQL statement somewhere.
	 *
	 * @param   string      $sql     The SQL to be executed.
	 * @param   array|null  $params  The SQL parameters.
	 * @param   array|null  $types   The SQL parameter types.
	 *
	 * @return void
	 */
	public function startQuery($sql, array $params = null, array $types = null)
	{
		$record = [
			'context' => [
				'sql' => $sql,
				'params' => $params,
				'types' => $types,
				'trace' => debug_backtrace()
			]
		];

		$this->debugger->addDatabaseEntry($record);
	}

	/**
	 * Marks the last started query as stopped. This can be used for timing of queries.
	 *
	 * @return void
	 */
	public function stopQuery()
	{
	}
}
