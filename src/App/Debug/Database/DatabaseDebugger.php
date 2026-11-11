<?php

/**
 * Part of the Joomla Tracker's Debug Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Debug\Database;

use App\Debug\Format\Html\TableFormat;
use Joomla\Database\DatabaseDriver;

/**
 * Class DatabaseDebugger.
 *
 * @since  1.0
 */
class DatabaseDebugger
{
    /**
     * Database object
     *
     * @var    DatabaseDriver
     * @since  1.0
     */
    private $database;

    /**
     * Constructor.
     *
     * @param   DatabaseDriver  $database  The database driver.
     *
     * @since   1.0
     */
    public function __construct(DatabaseDriver $database)
    {
        $this->database = $database;
    }

    /**
     * Get the database prefix.
     *
     * @return  string
     *
     * @since   1.0
     */
    public function getPrefix()
    {
        return $this->database->getPrefix();
    }

    /**
     * Get a database explain statement.
     *
     * @param   string  $query  The query to explain.
     *
     * @return  string  Formatted output
     *
     * @since   1.0
     */
    public function getExplain($query)
    {
        $db = $this->database;

        // Run an EXPLAIN query on the SQL query if possible:
        $explain = '';

        if (\in_array($db->getName(), ['mysqli', 'mysql', 'postgresql'])) {
            $dbVersion56 = (strncmp($db->getName(), 'mysql', 5) == 0) && version_compare($db->getVersion(), '5.6', '>=');

            if ((stripos($query, 'select') === 0) || ($dbVersion56 && ((stripos($query, 'delete') === 0) || (stripos($query, 'update') === 0)))) {
                $db->setQuery('EXPLAIN ' . $query);

                if ($db->execute()) {
                    $explain = (new TableFormat())->fromArray($db->loadAssocList());
                } else {
                    $explain = \sprintf('Failed EXPLAIN on query: %s', htmlspecialchars($query));
                }
            }
        }

        return $explain;
    }
}
