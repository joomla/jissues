<?php

/**
 * Part of the Joomla! Tracker Package.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace JTracker\Tests\Command\Install;

use JTracker\Command\Install\Install;
use JTracker\Tests\Command\CommandTestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

/**
 * Tests for the install command.
 *
 * The installer calls the static DatabaseDriver::splitSql(), which only a mock object (not a stub) can keep.
 *
 * @since  1.0
 */
#[AllowMockObjectsWithoutExpectations]
class InstallTest extends CommandTestCase
{
    /**
     * Create a database mock for the installer.
     *
     * @param   array   $tables   The existing tables.
     * @param   array   $dropped  Receives the dropped tables.
     * @param   string  $prefix   The table prefix.
     *
     * @return  object
     *
     * @since   1.0
     */
    private function createInstallerDatabase(array $tables, \ArrayObject $dropped, string $prefix = 'jos_')
    {
        $db = $this->createDatabaseMock($prefix, true);

        $db->method('getTableList')->willReturn($tables);
        $db->method('dropTable')
            ->willReturnCallback(
                static function ($table, $ifExists) use ($db, $dropped) {
                    $dropped[] = [$table, $ifExists];

                    return $db;
                }
            );
        $db->method('replacePrefix')->willReturnCallback(static fn ($sql) => str_replace('#__', $prefix, $sql));

        return $db;
    }

    /**
     * A reinstall only drops the tables carrying the configured prefix.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testReinstallDropsOnlyPrefixedTables(): void
    {
        $dropped = new \ArrayObject();

        $db = $this->createInstallerDatabase(
            ['jos_issues', 'jos_tracker_labels', 'other_users', 'sqlite_sequence', 'wp_jos_posts'],
            $dropped
        );

        $exitCode = $this->runCommand(new Install(), ['--reinstall' => true], $db, null, ['database' => ['driver' => 'mysqli']]);

        $this->assertSame(0, $exitCode);
        $this->assertSame([['jos_issues', true], ['jos_tracker_labels', true]], $dropped->getArrayCopy());

        $queries = array_map('trim', $this->getDbQueries());

        // Foreign key checks are switched off around the drops and the schema from etc/mysql.sql is created afterwards
        $this->assertSame(['SET FOREIGN_KEY_CHECKS=0', 'SET FOREIGN_KEY_CHECKS=1'], \array_slice($queries, 0, 2));
        $this->assertCount(1, preg_grep('/^CREATE TABLE IF NOT EXISTS `jos_issues` \(/', $queries));
        $this->assertSame([], preg_grep('/#__/', $queries));
    }

    /**
     * Nothing is dropped when no table carries the prefix.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testReinstallKeepsForeignTables(): void
    {
        $dropped = new \ArrayObject();

        $db = $this->createInstallerDatabase(['users', 'wp_posts'], $dropped, 'jt_');

        $this->runCommand(new Install(), ['--reinstall' => true], $db, null, ['database' => ['driver' => 'mysql']]);

        $this->assertCount(0, $dropped);
    }

    /**
     * Without --reinstall and without confirmation nothing is touched.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testExistingDatabaseIsKeptWithoutConfirmation(): void
    {
        $dropped = new \ArrayObject();

        $db = $this->createInstallerDatabase(['jos_issues'], $dropped);

        $this->assertSame(0, $this->runCommand(new Install(), [], $db, null, ['database' => ['driver' => 'mysql']]));

        $this->assertCount(0, $dropped);
        $this->assertSame([], $this->getDbQueries());
    }

    /**
     * An unknown database driver is rejected.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testUnknownDriverIsRejected(): void
    {
        $db = $this->createInstallerDatabase([], new \ArrayObject());

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Install SQL file for nosuchdb not found.');

        $this->runCommand(new Install(), ['--reinstall' => true], $db, null, ['database' => ['driver' => 'nosuchdb']]);
    }
}
