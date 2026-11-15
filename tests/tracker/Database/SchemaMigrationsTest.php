<?php

/**
 * Part of the Joomla! Tracker Package.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Tracker\Tests\Database;

use PHPUnit\Framework\TestCase;

/**
 * Checks that the install schema and the migrations agree.
 *
 * A fresh install imports etc/mysql.sql, which already contains every schema change. The migrations listed in its
 * #__migrations INSERT are marked as applied. A migration missing from that list would be run again on top of the
 * finished schema and fail.
 *
 * @since  1.0
 */
class SchemaMigrationsTest extends TestCase
{
    /**
     * Every migration file is marked as applied by the install schema.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testInstallSchemaMarksEveryMigrationAsApplied(): void
    {
        $etc = \dirname(__DIR__, 3) . '/etc';

        $files = array_map(
            static fn (string $file): string => basename($file, '.sql'),
            glob($etc . '/migrations/*.sql')
        );

        $schema = file_get_contents($etc . '/mysql.sql');

        $this->assertSame(1, preg_match('/INSERT INTO `#__migrations` \(`version`\) VALUES(.*?);/s', $schema, $insert));
        preg_match_all("/'(\d{11})'/", $insert[1], $listed);

        sort($files);
        $versions = $listed[1];
        sort($versions);

        $this->assertSame($files, $versions);
    }

    /**
     * Every table created by a migration also exists in the install schema.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testTablesCreatedByMigrationsExistInInstallSchema(): void
    {
        $etc    = \dirname(__DIR__, 3) . '/etc';
        $schema = file_get_contents($etc . '/mysql.sql');

        foreach (glob($etc . '/migrations/*.sql') as $file) {
            preg_match_all('/CREATE TABLE (?:IF NOT EXISTS )?`(#__\w+)`/', file_get_contents($file), $tables);

            foreach ($tables[1] as $table) {
                $this->assertMatchesRegularExpression(
                    '/CREATE TABLE (?:IF NOT EXISTS )?`' . preg_quote($table, '/') . '`/',
                    $schema,
                    basename($file) . ' creates ' . $table . ', which is missing in etc/mysql.sql'
                );
            }
        }
    }
}
