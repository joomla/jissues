<?php

/**
 * Part of the Joomla! Tracker Package.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace JTracker\Tests\Database;

use Joomla\Database\DatabaseDriver;
use Joomla\Database\Mysqli\MysqliQuery;
use JTracker\Tests\Stubs\TestTable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests for \JTracker\Database\AbstractDatabaseTable.
 *
 * @since  1.0
 */
class AbstractDatabaseTableTest extends TestCase
{
    /**
     * Mocked database driver.
     *
     * @var    DatabaseDriver|MockObject
     * @since  1.0
     */
    private $db;

    /**
     * Queries passed to the database mock.
     *
     * @var    MysqliQuery[]
     * @since  1.0
     */
    private $queries = [];

    /**
     * Sets up the fixture.
     *
     * @return  void
     *
     * @since   1.0
     */
    protected function setUp(): void
    {
        TestTable::clearFieldsCache();

        $this->db = $this->createMock(DatabaseDriver::class);

        $this->db->method('getTableColumns')
            ->with(TestTable::TABLE_NAME, false)
            ->willReturn(
                [
                    'id'          => (object) ['Field' => 'id', 'Default' => null],
                    'title'       => (object) ['Field' => 'title', 'Default' => ''],
                    'description' => (object) ['Field' => 'description', 'Default' => null],
                    'state'       => (object) ['Field' => 'state', 'Default' => '1'],
                ]
            );
        $this->db->method('getQuery')->willReturnCallback(
            function () {
                return new MysqliQuery($this->db);
            }
        );
        $this->db->method('quoteName')->willReturnCallback(
            static function ($name) {
                return '`' . $name . '`';
            }
        );
        $this->db->method('quote')->willReturnCallback(
            static function ($value) {
                return "'" . addslashes((string) $value) . "'";
            }
        );
        $this->db->method('setQuery')->willReturnCallback(
            function ($query) {
                $this->queries[] = $query;

                return $this->db;
            }
        );
    }

    /**
     * Tears down the fixture.
     *
     * @return  void
     *
     * @since   1.0
     */
    protected function tearDown(): void
    {
        TestTable::clearFieldsCache();
    }

    /**
     * Test that the constructor initialises all table fields with null.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testConstructorInitialisesFields(): void
    {
        $table = new TestTable($this->db);

        $this->assertEquals(
            (object) ['id' => null, 'title' => null, 'description' => null, 'state' => null],
            $table->getTableFields()
        );
        $this->assertSame(TestTable::TABLE_NAME, $table->getTableName());
        $this->assertSame('id', $table->getKeyName());
        $this->assertSame(['id'], $table->getKeyName(true));
    }

    /**
     * Test the constructor with multiple primary keys passed as object.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testConstructorWithObjectKeys(): void
    {
        $table = new TestTable($this->db, (object) ['id', 'state']);

        $this->assertSame('id', $table->getKeyName());
        $this->assertSame(['id', 'state'], $table->getKeyName(true));
    }

    /**
     * Test getKeyName() for a table without primary key.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testGetKeyNameWithoutKeys(): void
    {
        $this->assertSame('', (new TestTable($this->db, []))->getKeyName());
    }

    /**
     * Test that the columns of a table are looked up only once.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testGetFieldsIsCached(): void
    {
        $db = $this->createMock(DatabaseDriver::class);
        $db->expects($this->once())
            ->method('getTableColumns')
            ->willReturn(['id' => (object) ['Default' => null]]);

        $first  = new TestTable($db);
        $second = new TestTable($db);

        $this->assertSame($first->getFields(), $second->getFields());
        $this->assertArrayHasKey('id', $second->getFields());
    }

    /**
     * Test that a table without columns throws an exception.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testGetFieldsWithoutColumnsThrows(): void
    {
        $db = $this->createMock(DatabaseDriver::class);
        $db->method('getTableColumns')->willReturn([]);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('No columns found for #__test_table table');

        new TestTable($db);
    }

    /**
     * Test getting and setting a known field.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testMagicGetSetKnownField(): void
    {
        $table = new TestTable($this->db);

        $table->title = 'Hello';

        $this->assertSame('Hello', $table->title);
        $this->assertSame('Hello', $table->getTableFields()->title);
    }

    /**
     * Test that null valued fields can be read and written.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testMagicGetSetNullValuedField(): void
    {
        $table = new TestTable($this->db);

        $this->assertNull($table->description);

        $table->description = 'Text';
        $table->description = null;

        $this->assertNull($table->description);
    }

    /**
     * Test that reading an unknown field throws an exception.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testMagicGetUnknownFieldThrows(): void
    {
        $table = new TestTable($this->db);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Get unknown property: foo');

        $this->assertNull($table->foo);
    }

    /**
     * Test that writing an unknown field throws an exception and does not add the field.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testMagicSetUnknownFieldThrows(): void
    {
        $table = new TestTable($this->db);

        try {
            $table->foo = 'bar';

            $this->fail('Setting an unknown field did not throw an exception.');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('Set unknown property: foo', $e->getMessage());
        }

        $this->assertFalse(property_exists($table->getTableFields(), 'foo'));
    }

    /**
     * Test binding an array.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testBindArray(): void
    {
        $table = new TestTable($this->db);

        $this->assertSame($table, $table->bind(['id' => 5, 'title' => 'Title', 'description' => null]));

        $this->assertSame(5, $table->id);
        $this->assertSame('Title', $table->title);
        $this->assertNull($table->description);
        $this->assertNull($table->state);
    }

    /**
     * Test binding an object.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testBindObject(): void
    {
        $table = new TestTable($this->db);

        $table->bind((object) ['title' => 'Title', 'state' => 0]);

        $this->assertSame('Title', $table->title);
        $this->assertSame(0, $table->state);
    }

    /**
     * Test that unknown keys are not bound.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testBindIgnoresUnknownKeys(): void
    {
        $table = new TestTable($this->db);

        $table->bind(['title' => 'Title', 'foo' => 'bar']);

        $this->assertSame('Title', $table->title);
        $this->assertFalse(property_exists($table->getTableFields(), 'foo'));
    }

    /**
     * Test binding with ignored keys given as array.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testBindWithIgnoreArray(): void
    {
        $table = new TestTable($this->db);

        $table->bind(['id' => 5, 'title' => 'Title', 'state' => 2], ['id', 'state']);

        $this->assertNull($table->id);
        $this->assertSame('Title', $table->title);
        $this->assertNull($table->state);
    }

    /**
     * Test binding with ignored keys given as space separated string.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testBindWithIgnoreString(): void
    {
        $table = new TestTable($this->db);

        $table->bind(['id' => 5, 'title' => 'Title', 'state' => 2], 'id state');

        $this->assertNull($table->id);
        $this->assertSame('Title', $table->title);
        $this->assertNull($table->state);
    }

    /**
     * Test that binding a scalar value throws an exception.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testBindScalarThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('bind(*string*)');

        (new TestTable($this->db))->bind('title=foo');
    }

    /**
     * Test that reset() restores the defaults except for the primary key.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testReset(): void
    {
        $table = new TestTable($this->db);
        $table->bind(['id' => 5, 'title' => 'Title', 'description' => 'Text', 'state' => 0]);

        $table->reset();

        $this->assertSame(5, $table->id);
        $this->assertSame('', $table->title);
        $this->assertNull($table->description);
        $this->assertSame('1', $table->state);
    }

    /**
     * Test loading a row by primary key value.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testLoadByPrimaryKey(): void
    {
        $this->db->expects($this->once())
            ->method('loadAssoc')
            ->willReturn(['id' => 5, 'title' => 'Title', 'description' => null, 'state' => 1]);

        $table = new TestTable($this->db);

        $this->assertSame($table, $table->load(5));

        $this->assertSame(5, $table->id);
        $this->assertSame('Title', $table->title);
        $this->assertSame(1, $table->state);

        $this->assertCount(1, $this->queries);

        $sql = (string) $this->queries[0];

        $this->assertStringContainsString('FROM `#__test_table`', $sql);
        $this->assertStringContainsString("`id` = '5'", $sql);
    }

    /**
     * Test loading a row by an array of fields, including a null valued one.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testLoadByKeyArray(): void
    {
        $this->db->method('loadAssoc')
            ->willReturn(['id' => 7, 'title' => 'Title', 'description' => 'Text', 'state' => 1]);

        $table = new TestTable($this->db);

        $table->load(['title' => 'Title', 'description' => 'Text']);

        $this->assertSame(7, $table->id);

        $sql = (string) $this->queries[0];

        $this->assertStringContainsString("`title` = 'Title'", $sql);
        $this->assertStringContainsString("`description` = 'Text'", $sql);
    }

    /**
     * Test that loading by an unknown field throws an exception before querying the database.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testLoadByUnknownKeyThrows(): void
    {
        $this->db->expects($this->never())->method('loadAssoc');

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Missing field in database');

        (new TestTable($this->db))->load(['foo' => 'bar']);
    }

    /**
     * Test that loading a missing row throws an exception.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testLoadEmptyResultThrows(): void
    {
        $this->db->method('loadAssoc')->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('can not bind keys "id=99" for table #__test_table due to an empty result set');

        (new TestTable($this->db))->load(99);
    }

    /**
     * Test that load() without keys and without primary key value does nothing.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testLoadWithoutKeysAndEmptyPrimaryKey(): void
    {
        $this->db->expects($this->never())->method('loadAssoc');

        $table        = new TestTable($this->db);
        $table->title = 'Title';

        $this->assertSame($table, $table->load());
        $this->assertSame('Title', $table->title);
        $this->assertSame([], $this->queries);
    }

    /**
     * Test that load() without keys uses the current primary key value.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testLoadWithoutKeysUsesPrimaryKeyValue(): void
    {
        $this->markTestIncomplete('Known issue: empty($this->$key) in load() ignores the primary key value because there is no __isset().');

        $this->db->method('loadAssoc')->willReturn(['id' => 3, 'title' => 'Loaded']);

        $table        = new TestTable($this->db);
        $table->id    = 3;
        $table->title = 'Title';

        $table->load();

        $this->assertSame('Loaded', $table->title);
        $this->assertStringContainsString("`id` = '3'", (string) $this->queries[0]);
    }

    /**
     * Test that load() resets the fields before binding the loaded row.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testLoadResetsFields(): void
    {
        $this->db->method('loadAssoc')->willReturn(['id' => 3, 'title' => 'Loaded']);

        $table              = new TestTable($this->db);
        $table->description = 'Stale';
        $table->state       = 0;

        $table->load(3);

        $this->assertNull($table->description);
        $this->assertSame('1', $table->state);
    }

    /**
     * Test that load() keeps the fields when reset is disabled.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testLoadWithoutReset(): void
    {
        $this->db->method('loadAssoc')->willReturn(['id' => 3, 'title' => 'Loaded']);

        $table              = new TestTable($this->db);
        $table->description = 'Kept';

        $table->load(3, false);

        $this->assertSame('Kept', $table->description);
        $this->assertSame('Loaded', $table->title);
    }

    /**
     * Test that a single value can not be used for a table with multiple primary keys.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testLoadSingleValueWithMultiplePrimaryKeysThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new TestTable($this->db, ['id', 'state']))->load(5);
    }

    /**
     * Test that a single value can not be used for a table without primary key.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testLoadSingleValueWithoutPrimaryKeyThrows(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No table keys defined.');

        (new TestTable($this->db, []))->load(5);
    }

    /**
     * Test that check() returns the table.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testCheck(): void
    {
        $table = new TestTable($this->db);

        $this->assertSame($table, $table->check());
    }

    /**
     * Test that store() inserts a row without primary key value.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testStoreInsertsNewRow(): void
    {
        $table        = new TestTable($this->db);
        $table->title = 'New';

        $this->db->expects($this->never())->method('updateObject');
        $this->db->expects($this->once())
            ->method('insertObject')
            ->with(TestTable::TABLE_NAME, $this->identicalTo($table->getTableFields()), 'id');

        $this->assertFalse($table->hasPrimaryKey());
        $this->assertSame($table, $table->store());
    }

    /**
     * Test that store() updates a row with primary key value.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testStoreUpdatesExistingRow(): void
    {
        $table        = new TestTable($this->db);
        $table->id    = 5;
        $table->title = 'Changed';

        $this->db->expects($this->never())->method('insertObject');
        $this->db->expects($this->once())
            ->method('updateObject')
            ->with(TestTable::TABLE_NAME, $this->identicalTo($table->getTableFields()), ['id'], true);

        $this->assertTrue($table->hasPrimaryKey());
        $table->store(true);
    }

    /**
     * Test that save() binds, checks and stores the data.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testSave(): void
    {
        $table = new TestTable($this->db);

        $this->db->expects($this->once())
            ->method('updateObject')
            ->with(
                TestTable::TABLE_NAME,
                $this->callback(
                    static function ($fields) {
                        return $fields->id === 5 && $fields->title === 'Saved' && $fields->state === null;
                    }
                ),
                ['id'],
                false
            );

        $this->assertSame($table, $table->save(['id' => 5, 'title' => 'Saved', 'state' => 2], 'state'));
    }

    /**
     * Test hasPrimaryKey() for a table with multiple primary keys.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testHasPrimaryKeyWithMultipleKeys(): void
    {
        $this->db->method('loadResult')->willReturnOnConsecutiveCalls(1, 0);

        $table        = new TestTable($this->db, ['id', 'state']);
        $table->id    = 5;
        $table->state = 1;

        $this->assertTrue($table->hasPrimaryKey());
        $this->assertFalse($table->hasPrimaryKey());

        $sql = (string) $this->queries[0];

        $this->assertStringContainsString('COUNT(*)', $sql);
        $this->assertStringContainsString("`id` = '5'", $sql);
        $this->assertStringContainsString("`state` = '1'", $sql);
    }

    /**
     * Test appendPrimaryKeys() with an explicit primary key value.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testAppendPrimaryKeysWithValue(): void
    {
        $table = new TestTable($this->db);
        $query = new MysqliQuery($this->db);

        $this->assertSame($table, $table->appendPrimaryKeys($query, '7'));
        $this->assertStringContainsString("`id` = '7'", (string) $query->where);

        $composite = new TestTable($this->db, ['id', 'state']);
        $query     = new MysqliQuery($this->db);

        $composite->appendPrimaryKeys($query, ['id' => 7, 'state' => 2]);

        $this->assertStringContainsString("`id` = '7' AND `state` = '2'", (string) $query->where);
    }

    /**
     * Test deleting a row by primary key.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testDelete(): void
    {
        $this->db->expects($this->once())->method('execute');

        $table = new TestTable($this->db);

        $this->assertSame($table, $table->delete(8));

        $sql = (string) $this->queries[0];

        $this->assertStringContainsString('DELETE', $sql);
        $this->assertStringContainsString('`#__test_table`', $sql);
        $this->assertStringContainsString("`id` = '8'", $sql);
    }

    /**
     * Test that delete() uses the current primary key value.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testDeleteUsesPrimaryKeyValue(): void
    {
        $this->db->expects($this->once())->method('execute');

        $table     = new TestTable($this->db);
        $table->id = 4;

        $table->delete();

        $this->assertStringContainsString("`id` = '4'", (string) $this->queries[0]);
    }

    /**
     * Test that delete() without primary key value throws an exception.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testDeleteWithoutPrimaryKeyThrows(): void
    {
        $this->db->expects($this->never())->method('execute');

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Null primary key not allowed.');

        (new TestTable($this->db))->delete();
    }

    /**
     * Test iterating over the table fields.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testGetIterator(): void
    {
        $table = new TestTable($this->db);
        $table->bind(['id' => 1, 'title' => 'Title']);

        $this->assertInstanceOf(\ArrayIterator::class, $table->getIterator());
        $this->assertSame(
            ['id' => 1, 'title' => 'Title', 'description' => null, 'state' => null],
            iterator_to_array($table)
        );
    }
}
