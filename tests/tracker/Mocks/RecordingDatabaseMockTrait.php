<?php

/**
 * Part of the Joomla! Tracker Package.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace JTracker\Tests\Mocks;

use Joomla\Database\DatabaseDriver;
use Joomla\Database\Mysqli\MysqliQuery;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Builds a DatabaseDriver mock that records the executed SQL and serves canned results.
 *
 * No database connection is ever made. Results are registered with addDbResult(): the first
 * registered regular expression matching the current SQL string (with the "#__" prefix
 * left in place) provides the value returned by the load*() methods.
 *
 * @since  1.0
 */
trait RecordingDatabaseMockTrait
{
    /**
     * List of executed queries as [method, sql] pairs.
     *
     * @var    array
     * @since  1.0
     */
    protected $dbLog = [];

    /**
     * Recorded insertObject() calls as [table, object] pairs.
     *
     * @var    array
     * @since  1.0
     */
    protected $dbInserts = [];

    /**
     * Recorded updateObject() calls as [table, object, keys] pairs.
     *
     * @var    array
     * @since  1.0
     */
    protected $dbUpdates = [];

    /**
     * Registered results as [regex, result] pairs.
     *
     * @var    array
     * @since  1.0
     */
    protected $dbResults = [];

    /**
     * Column definitions returned by getTableColumns() keyed by table name.
     *
     * @var    array
     * @since  1.0
     */
    protected $dbColumns = [];

    /**
     * Register a result for queries matching the given regular expression.
     *
     * @param   string  $regex   Regular expression matched against the SQL.
     * @param   mixed   $result  The result, or a callable receiving the SQL and the load method.
     *
     * @return  void
     *
     * @since   1.0
     */
    protected function addDbResult(string $regex, $result): void
    {
        $this->dbResults[] = [$regex, $result];
    }

    /**
     * Register the column names of a table.
     *
     * @param   string  $table    The table name, e.g. "#__tracker_labels".
     * @param   array   $columns  The column names.
     *
     * @return  void
     *
     * @since   1.0
     */
    protected function addDbTableColumns(string $table, array $columns): void
    {
        $fields = [];

        foreach ($columns as $column) {
            $fields[$column] = (object) ['Field' => $column, 'Default' => null];
        }

        $this->dbColumns[$table] = $fields;
    }

    /**
     * Get the SQL of all recorded queries.
     *
     * @return  string[]
     *
     * @since   1.0
     */
    protected function getDbQueries(): array
    {
        return array_column($this->dbLog, 1);
    }

    /**
     * Create the database mock.
     *
     * @param   string  $prefix  The table prefix.
     *
     * @return  DatabaseDriver&MockObject
     *
     * @since   1.0
     */
    protected function createDatabaseMock(string $prefix = 'jos_'): DatabaseDriver
    {
        // Mock everything except the static helpers (e.g. splitSql()), which mocks can not serve
        $methods = [];

        foreach ((new \ReflectionClass(DatabaseDriver::class))->getMethods() as $method) {
            if ($method->isAbstract()) {
                $methods[] = $method->getName();

                continue;
            }

            if (!$method->isPublic() || $method->isConstructor() || $method->isDestructor() || $method->isFinal() || $method->isStatic()) {
                continue;
            }

            $methods[] = $method->getName();
        }

        $db = $this->getMockBuilder(DatabaseDriver::class)
            ->disableOriginalConstructor()
            ->onlyMethods($methods)
            ->getMock();

        $current = '';

        $db->method('getQuery')
            ->willReturnCallback(
                static function () use ($db) {
                    return new MysqliQuery($db);
                }
            );

        $db->method('setQuery')
            ->willReturnCallback(
                static function ($query) use ($db, &$current) {
                    $current = (string) $query;

                    return $db;
                }
            );

        $db->method('quoteName')
            ->willReturnCallback(
                static function ($name) {
                    if (\is_array($name)) {
                        return array_map(static fn ($n) => '`' . $n . '`', $name);
                    }

                    return '`' . str_replace('.', '`.`', $name) . '`';
                }
            );

        $db->method('quote')
            ->willReturnCallback(
                static function ($text) {
                    if (\is_array($text)) {
                        return array_map(static fn ($t) => "'" . addslashes((string) $t) . "'", $text);
                    }

                    return "'" . addslashes((string) $text) . "'";
                }
            );

        $db->method('escape')
            ->willReturnCallback(static fn ($text) => addslashes((string) $text));

        $db->method('getPrefix')->willReturn($prefix);
        $db->method('getDateFormat')->willReturn('Y-m-d H:i:s');
        $db->method('getNullDate')->willReturn('0000-00-00 00:00:00');

        $db->method('getTableColumns')
            ->willReturnCallback(fn ($table) => $this->dbColumns[$table] ?? []);

        $db->method('insertObject')
            ->willReturnCallback(
                function ($table, &$object, $key = null) {
                    $this->dbInserts[] = [$table, clone $object];

                    return true;
                }
            );

        $db->method('updateObject')
            ->willReturnCallback(
                function ($table, &$object, $key, $nulls = false) {
                    $this->dbUpdates[] = [$table, clone $object, $key];

                    return true;
                }
            );

        foreach (['loadAssoc', 'loadAssocList', 'loadColumn', 'loadObject', 'loadObjectList', 'loadResult', 'loadRow', 'loadRowList'] as $method) {
            $db->method($method)
                ->willReturnCallback(
                    function () use ($method, &$current) {
                        $this->dbLog[] = [$method, $current];

                        return $this->resolveDbResult($current, $method);
                    }
                );
        }

        $db->method('execute')
            ->willReturnCallback(
                function () use (&$current) {
                    $this->dbLog[] = ['execute', $current];

                    return true;
                }
            );

        return $db;
    }

    /**
     * Find the registered result for a query.
     *
     * @param   string  $sql     The SQL string.
     * @param   string  $method  The load method that was called.
     *
     * @return  mixed
     *
     * @since   1.0
     */
    private function resolveDbResult(string $sql, string $method)
    {
        foreach ($this->dbResults as [$regex, $result]) {
            if (preg_match($regex, $sql)) {
                return \is_callable($result) ? $result($sql, $method) : $result;
            }
        }

        return \in_array($method, ['loadAssocList', 'loadColumn', 'loadObjectList', 'loadRowList'], true) ? [] : null;
    }
}
