<?php

/**
 * Part of the Joomla! Tracker Package.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace JTracker\Tests\Projects;

use App\Projects\TrackerProject;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\Mysqli\MysqliQuery;
use JTracker\Database\AbstractDatabaseTable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests for \App\Projects\TrackerProject.
 *
 * @since  1.0
 */
class TrackerProjectTest extends TestCase
{
    /**
     * Project data as stored in the database.
     *
     * @var    array
     * @since  1.0
     */
    private const DATA = [
        'project_id'       => 3,
        'title'            => 'Joomla! CMS',
        'alias'            => 'joomla-cms',
        'gh_user'          => 'joomla',
        'gh_project'       => 'joomla-cms',
        'gh_editbot_user'  => 'bot',
        'gh_editbot_pass'  => 'secret',
        'gh_editbot_token' => 'ghp_token',
        'ext_tracker_link' => 'https://issues.example.org/%d',
        'short_title'      => 'CMS',
    ];

    /**
     * Queries passed to the database mocks.
     *
     * @var    MysqliQuery[]
     * @since  1.0
     */
    private $queries = [];

    /**
     * Tears down the fixture.
     *
     * @return  void
     *
     * @since   1.0
     */
    protected function tearDown(): void
    {
        // Reset the static fields cache of the database tables used by the project.
        $property = new \ReflectionProperty(AbstractDatabaseTable::class, 'fieldsCache');
        $property->setAccessible(true);
        $property->setValue(null, []);
    }

    /**
     * Create a database mock which answers loadObjectList() with the given results.
     *
     * @param   array  $results  The results for consecutive loadObjectList() calls.
     *
     * @return  DatabaseDriver|MockObject
     *
     * @since   1.0
     */
    private function createDatabase(array $results = [])
    {
        $db = $this->createMock(DatabaseDriver::class);

        $db->method('getQuery')->willReturnCallback(
            static function () use ($db) {
                return new MysqliQuery($db);
            }
        );
        $db->method('quoteName')->willReturnCallback(
            static function ($name) {
                return '`' . $name . '`';
            }
        );
        $db->method('getTableColumns')->willReturn(
            ['id' => (object) ['Field' => 'id', 'Default' => null]]
        );
        $db->method('setQuery')->willReturnCallback(
            function ($query) use ($db) {
                $this->queries[] = $query;

                return $db;
            }
        );
        $db->expects($this->exactly(\count($results)))
            ->method('loadObjectList')
            ->willReturnOnConsecutiveCalls(...$results);

        return $db;
    }

    /**
     * Test that the constructor binds the project data.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testConstructorAndGetters(): void
    {
        $project = new TrackerProject($this->createDatabase(), (object) self::DATA);

        $this->assertSame(3, $project->getProject_Id());
        $this->assertSame('Joomla! CMS', $project->getTitle());
        $this->assertSame('joomla-cms', $project->getAlias());
        $this->assertSame('joomla', $project->getGh_User());
        $this->assertSame('joomla-cms', $project->getGh_Project());
        $this->assertSame('bot', $project->getGh_Editbot_User());
        $this->assertSame('secret', $project->getGh_Editbot_Pass());
        $this->assertSame('ghp_token', $project->getGh_Editbot_Token());
        $this->assertSame('https://issues.example.org/%d', $project->getExt_Tracker_Link());
        $this->assertSame('CMS', $project->getShort_Title());
    }

    /**
     * Test the detection of a configured edit bot.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testHasEditBot(): void
    {
        $data = static function (string $user, string $password, string $token): object {
            return (object) ['gh_editbot_user' => $user, 'gh_editbot_pass' => $password, 'gh_editbot_token' => $token];
        };

        $this->assertTrue((new TrackerProject($this->createDatabase(), $data('bot', '', 'ghp_token')))->hasEditBot());
        $this->assertTrue((new TrackerProject($this->createDatabase(), $data('bot', 'secret', '')))->hasEditBot());
        $this->assertFalse((new TrackerProject($this->createDatabase(), $data('bot', '', '')))->hasEditBot());
        $this->assertFalse((new TrackerProject($this->createDatabase(), $data('', 'secret', '')))->hasEditBot());
        $this->assertFalse((new TrackerProject($this->createDatabase()))->hasEditBot());
    }

    /**
     * Test the defaults of a project created without data.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testConstructorWithoutData(): void
    {
        $project = new TrackerProject($this->createDatabase());

        $this->assertSame(0, $project->getProject_Id());
        $this->assertNull($project->getTitle());
        $this->assertNull($project->getAlias());
    }

    /**
     * Test that the constructor rejects unknown keys.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testConstructorRejectsUnknownKey(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('unexpected key: foo');

        new TrackerProject($this->createDatabase(), ['title' => 'Test', 'foo' => 'bar']);
    }

    /**
     * Test the magic getter.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testMagicGet(): void
    {
        $project = new TrackerProject($this->createDatabase(), ['project_id' => 7, 'title' => 'Test', 'alias' => 'test']);

        $this->assertSame(7, $project->project_id);
        $this->assertSame('Test', $project->title);
        $this->assertSame('test', $project->alias);
    }

    /**
     * Test the magic getter for properties without value and unknown properties.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testMagicGetNotSet(): void
    {
        $project = new TrackerProject($this->createDatabase(), ['project_id' => 7]);

        $this->assertSame('not set..', $project->gh_user);
        $this->assertSame('not set..', $project->foo);
    }

    /**
     * Test that getLabels() builds the label list and queries the database only once.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testGetLabels(): void
    {
        $db = $this->createDatabase(
            [
                [
                    (object) ['label_id' => 10, 'name' => 'bug', 'color' => 'ff0000'],
                    (object) ['label_id' => 11, 'name' => 'feature', 'color' => '00ff00'],
                ],
            ]
        );

        $project = new TrackerProject($db, ['project_id' => 3]);

        $expected = [
            10 => (object) ['name' => 'bug', 'color' => 'ff0000'],
            11 => (object) ['name' => 'feature', 'color' => '00ff00'],
        ];

        $this->assertEquals($expected, $project->getLabels());
        $this->assertEquals($expected, $project->getLabels());

        $this->assertCount(1, $this->queries);

        $sql = (string) $this->queries[0];

        $this->assertStringContainsString('`#__tracker_labels`', $sql);
        $this->assertStringContainsString('`project_id` = 3', $sql);

        // The labels are listed alphabetically, e.g. in the label filter of the issue list
        $this->assertStringContainsString('ORDER BY `name`', $sql);
    }

    /**
     * Test that an empty label list is cached as well.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testGetLabelsCachesEmptyResult(): void
    {
        $project = new TrackerProject($this->createDatabase([[]]), ['project_id' => 3]);

        $this->assertSame([], $project->getLabels());
        $this->assertSame([], $project->getLabels());
    }

    /**
     * Test that two project instances do not share their labels.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testGetLabelsIsCachedPerInstance(): void
    {
        $db = $this->createDatabase(
            [
                [(object) ['label_id' => 1, 'name' => 'cms', 'color' => '111111']],
                [(object) ['label_id' => 2, 'name' => 'framework', 'color' => '222222']],
            ]
        );

        $cms       = new TrackerProject($db, ['project_id' => 1]);
        $framework = new TrackerProject($db, ['project_id' => 2]);

        $this->assertSame([1], array_keys($cms->getLabels()));
        $this->assertSame([2], array_keys($framework->getLabels()));

        // Both lists are still served from the respective instance cache.
        $this->assertSame([1], array_keys($cms->getLabels()));
        $this->assertSame([2], array_keys($framework->getLabels()));

        $this->assertStringContainsString('`project_id` = 1', (string) $this->queries[0]);
        $this->assertStringContainsString('`project_id` = 2', (string) $this->queries[1]);
    }

    /**
     * Test that getMilestones() queries the database only once.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testGetMilestones(): void
    {
        $milestones = [
            (object) ['milestone_id' => 1, 'milestone_number' => 5, 'title' => '4.0', 'description' => '', 'state' => 'open', 'due_on' => null],
        ];

        $project = new TrackerProject($this->createDatabase([$milestones]), ['project_id' => 3]);

        $this->assertSame($milestones, $project->getMilestones());
        $this->assertSame($milestones, $project->getMilestones());

        $this->assertCount(1, $this->queries);

        $sql = (string) $this->queries[0];

        $this->assertStringContainsString('`#__tracker_milestones`', $sql);
        $this->assertStringContainsString('`project_id` = 3', $sql);
        $this->assertStringContainsString('ORDER BY `title`', $sql);
    }

    /**
     * Test that two project instances do not share their milestones.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testGetMilestonesIsCachedPerInstance(): void
    {
        $first  = [(object) ['milestone_id' => 1, 'title' => '4.0']];
        $second = [(object) ['milestone_id' => 2, 'title' => '2.0']];

        $db = $this->createDatabase([$first, $second]);

        $cms       = new TrackerProject($db, ['project_id' => 1]);
        $framework = new TrackerProject($db, ['project_id' => 2]);

        $this->assertSame($first, $cms->getMilestones());
        $this->assertSame($second, $framework->getMilestones());
        $this->assertSame($first, $cms->getMilestones());
    }

    /**
     * Test that an empty milestone list is cached as well.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testGetMilestonesCachesEmptyResult(): void
    {
        $project = new TrackerProject($this->createDatabase([[]]), ['project_id' => 3]);

        $this->assertSame([], $project->getMilestones());
        $this->assertSame([], $project->getMilestones());
    }

    /**
     * Test that getCategories() queries the database only once.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testGetCategories(): void
    {
        $categories = [(object) ['id' => 1, 'title' => 'Administration', 'project_id' => 3]];

        $project = new TrackerProject($this->createDatabase([$categories]), ['project_id' => 3]);

        $this->assertSame($categories, $project->getCategories());
        $this->assertSame($categories, $project->getCategories());

        $this->assertCount(1, $this->queries);

        $sql = (string) $this->queries[0];

        $this->assertStringContainsString('`#__issues_categories`', $sql);
        $this->assertStringContainsString('`project_id` = 3', $sql);
        $this->assertStringContainsString('ORDER BY `title`', $sql);
    }

    /**
     * Test that two project instances do not share their categories.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testGetCategoriesIsCachedPerInstance(): void
    {
        $first  = [(object) ['id' => 1, 'title' => 'Administration']];
        $second = [(object) ['id' => 2, 'title' => 'Database']];

        $db = $this->createDatabase([$first, $second]);

        $cms       = new TrackerProject($db, ['project_id' => 1]);
        $framework = new TrackerProject($db, ['project_id' => 2]);

        $this->assertSame($first, $cms->getCategories());
        $this->assertSame($second, $framework->getCategories());
        $this->assertSame($first, $cms->getCategories());
    }

    /**
     * Test the legacy Serializable round trip.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testSerializeUnserialize(): void
    {
        $project = new TrackerProject(
            $this->createDatabase([[(object) ['label_id' => 1, 'name' => 'bug', 'color' => 'ff0000']]]),
            self::DATA
        );

        // Fill the cache, it must not be serialized.
        $project->getLabels();

        $serialized = $project->serialize();

        $this->assertStringNotContainsString('labelsCache', $serialized);
        $this->assertStringNotContainsString('milestonesCache', $serialized);
        $this->assertStringNotContainsString('categoriesCache', $serialized);
        $this->assertStringNotContainsString('"database"', $serialized);

        $copy = (new \ReflectionClass(TrackerProject::class))->newInstanceWithoutConstructor();
        $copy->unserialize($serialized);

        $this->assertSame(3, $copy->getProject_Id());
        $this->assertSame('Joomla! CMS', $copy->getTitle());
        $this->assertSame('joomla-cms', $copy->getAlias());
        $this->assertSame('CMS', $copy->getShort_Title());
        $this->assertSame(['view', 'create', 'edit', 'editown', 'manage'], $copy->getDefaultActions());
    }

    /**
     * Test the __serialize() / __unserialize() round trip.
     *
     * @return  void
     *
     * @since   2.0
     */
    public function testMagicSerializeExcludesCacheAndDatabase(): void
    {
        $project = new TrackerProject(
            $this->createDatabase(
                [
                    [(object) ['label_id' => 1, 'name' => 'bug', 'color' => 'ff0000']],
                    [(object) ['milestone_id' => 1]],
                    [(object) ['id' => 1]],
                ]
            ),
            self::DATA
        );

        $project->getLabels();
        $project->getMilestones();
        $project->getCategories();

        $data = $project->__serialize();

        $this->assertArrayNotHasKey('database', $data);
        $this->assertArrayNotHasKey('labelsCache', $data);
        $this->assertArrayNotHasKey('milestonesCache', $data);
        $this->assertArrayNotHasKey('categoriesCache', $data);
        $this->assertSame(3, $data['project_id']);
        $this->assertSame('Joomla! CMS', $data['title']);
    }

    /**
     * Test that a serialized and restored project loads its lists from the new database connection.
     *
     * @return  void
     *
     * @since   2.0
     */
    public function testUnserializedProjectQueriesFreshData(): void
    {
        $project = new TrackerProject(
            $this->createDatabase([[(object) ['label_id' => 1, 'name' => 'old', 'color' => '000000']]]),
            self::DATA
        );

        $project->getLabels();

        /** @var TrackerProject $copy */
        $copy = unserialize(serialize($project));

        $this->assertInstanceOf(TrackerProject::class, $copy);
        $this->assertNotSame($project, $copy);
        $this->assertSame(3, $copy->getProject_Id());
        $this->assertSame('Joomla! CMS', $copy->getTitle());
        $this->assertSame('joomla', $copy->getGh_User());
        $this->assertSame('joomla-cms', $copy->getGh_Project());

        $copy->setDatabase(
            $this->createDatabase([[(object) ['label_id' => 2, 'name' => 'new', 'color' => 'ffffff']]])
        );

        $this->assertEquals([2 => (object) ['name' => 'new', 'color' => 'ffffff']], $copy->getLabels());
    }
}
