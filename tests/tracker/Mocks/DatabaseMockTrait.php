<?php

/**
 * Part of the Joomla Tracker
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Tests\Mocks;

use Joomla\Database\DatabaseDriver;
use Joomla\Database\Mysqli\MysqliQuery;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Helper for test cases which need a database driver returning canned results.
 *
 * No database connection is made: the driver is a mock which builds real MySQLi queries
 * and returns the given rows from loadObjectList().
 *
 * @since  1.0
 */
trait DatabaseMockTrait
{
    /**
     * Queries passed to setQuery() as strings.
     *
     * @var  string[]
     */
    protected $executedQueries = [];

    /**
     * Creates a database driver mock which returns the given rows from loadObjectList().
     *
     * @param   array  $rows  The rows returned by loadObjectList()
     *
     * @return  DatabaseDriver|MockObject
     */
    protected function createDatabaseMock(array $rows): DatabaseDriver
    {
        $this->executedQueries = [];

        $db = $this->createMock(DatabaseDriver::class);

        $db->method('getQuery')->willReturnCallback(static function () use ($db) {
            return new MysqliQuery($db);
        });

        $db->method('quoteName')->willReturnCallback(static function ($name, $as = null) {
            $quote = static function ($name) {
                return '`' . str_replace('.', '`.`', $name) . '`';
            };

            if (\is_array($name)) {
                return array_map($quote, $name);
            }

            return $quote($name) . ($as === null ? '' : ' AS ' . $quote($as));
        });

        $db->method('setQuery')->willReturnCallback(function ($query) use ($db) {
            $this->executedQueries[] = (string) $query;

            return $db;
        });

        $db->method('loadObjectList')->willReturnCallback(static function () use ($rows) {
            // Return copies so modifications by the tested code do not leak between calls
            return array_map(static function ($row) {
                return (object) (array) $row;
            }, $rows);
        });

        return $db;
    }
}
