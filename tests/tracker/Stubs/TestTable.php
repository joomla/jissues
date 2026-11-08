<?php

/**
 * Part of the Joomla! Tracker Package.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace JTracker\Tests\Stubs;

use Joomla\Database\DatabaseDriver;
use JTracker\Database\AbstractDatabaseTable;

/**
 * Concrete database table for testing \JTracker\Database\AbstractDatabaseTable.
 *
 * @property   integer  $id           PK
 * @property   string   $title        Title
 * @property   string   $description  Description
 * @property   integer  $state        State
 *
 * @since  1.0
 */
class TestTable extends AbstractDatabaseTable
{
    /**
     * The name of the database table.
     *
     * @var    string
     * @since  1.0
     */
    public const TABLE_NAME = '#__test_table';

    /**
     * Constructor.
     *
     * @param   DatabaseDriver  $database  The database connector.
     * @param   mixed           $keys      The primary key(s).
     *
     * @since   1.0
     */
    public function __construct(DatabaseDriver $database, $keys = 'id')
    {
        parent::__construct(self::TABLE_NAME, $keys, $database);
    }

    /**
     * Get the fields of the table.
     *
     * @return  \stdClass
     *
     * @since   1.0
     */
    public function getTableFields(): \stdClass
    {
        return $this->tableFields;
    }

    /**
     * Clear the static cache of table columns.
     *
     * @return  void
     *
     * @since   1.0
     */
    public static function clearFieldsCache(): void
    {
        static::$fieldsCache = [];
    }
}
