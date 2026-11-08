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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the access map of \App\Projects\TrackerProject.
 *
 * @since  1.0
 */
class TrackerProjectAccessTest extends TestCase
{
    /**
     * Mocked database driver.
     *
     * @var    DatabaseDriver|MockObject
     * @since  1.0
     */
    private $db;

    /**
     * The query object handed out by the database mock.
     *
     * @var    MysqliQuery
     * @since  1.0
     */
    private $query;

    /**
     * Sets up the fixture.
     *
     * @return  void
     *
     * @since   1.0
     */
    protected function setUp(): void
    {
        $this->db    = $this->createMock(DatabaseDriver::class);
        $this->query = new MysqliQuery($this->db);

        $this->db->method('getQuery')->willReturn($this->query);
        $this->db->method('quoteName')->willReturnCallback(
            static function ($name) {
                return '`' . $name . '`';
            }
        );
    }

    /**
     * Create a group row as returned from the #__accessgroups table.
     *
     * @param   integer   $id       The group id.
     * @param   string    $title    The group title.
     * @param   boolean   $system   Whether the group is a system group.
     * @param   string[]  $actions  The allowed actions.
     *
     * @return  \stdClass
     *
     * @since   1.0
     */
    private function createGroup(int $id, string $title, bool $system, array $actions): \stdClass
    {
        $group = (object) [
            'group_id' => $id,
            'title'    => $title,
            'system'   => $system ? 1 : 0,
        ];

        foreach (['view', 'create', 'edit', 'editown', 'manage'] as $action) {
            $group->{'can_' . $action} = \in_array($action, $actions, true) ? 1 : 0;
        }

        return $group;
    }

    /**
     * Test getDefaultActions().
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testGetDefaultActions(): void
    {
        $project = new TrackerProject($this->db);

        $this->assertSame(['view', 'create', 'edit', 'editown', 'manage'], $project->getDefaultActions());
    }

    /**
     * Test that an invalid action is rejected.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testGetAccessGroupsInvalidAction(): void
    {
        $this->db->expects($this->never())->method('setQuery');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid action: admin');

        (new TrackerProject($this->db, ['project_id' => 1]))->getAccessGroups('admin');
    }

    /**
     * Test that an invalid filter is rejected.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testGetAccessGroupsInvalidFilter(): void
    {
        $this->db->expects($this->never())->method('setQuery');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid filter: Admin');

        (new TrackerProject($this->db, ['project_id' => 1]))->getAccessGroups('view', 'Admin');
    }

    /**
     * Test that a new project (without id) denies everything and does not query the database.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testGetAccessGroupsForNewProject(): void
    {
        $this->db->expects($this->never())->method('setQuery');

        $project = new TrackerProject($this->db);

        foreach ($project->getDefaultActions() as $action) {
            $this->assertSame(0, $project->getAccessGroups($action, 'Public'));
            $this->assertSame(0, $project->getAccessGroups($action, 'User'));
            $this->assertSame([], $project->getAccessGroups($action));
        }
    }

    /**
     * Test the access map built from the system and custom groups of a project.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testGetAccessGroupsForExistingProject(): void
    {
        $this->db->expects($this->once())
            ->method('setQuery')
            ->with($this->identicalTo($this->query))
            ->willReturnSelf();
        $this->db->expects($this->once())
            ->method('loadObjectList')
            ->willReturn(
                [
                    $this->createGroup(1, 'Public', true, ['view']),
                    $this->createGroup(2, 'User', true, ['view', 'create', 'editown']),
                    $this->createGroup(5, 'Maintainers', false, ['edit', 'manage']),
                    $this->createGroup(6, 'Testers', false, ['edit']),
                    $this->createGroup(7, 'Nobody', false, []),
                ]
            );

        $project = new TrackerProject($this->db, ['project_id' => 12]);

        // Public group
        $this->assertSame(1, $project->getAccessGroups('view', 'Public'));
        $this->assertSame(0, $project->getAccessGroups('create', 'Public'));
        $this->assertSame(0, $project->getAccessGroups('manage', 'Public'));

        // User group
        $this->assertSame(1, $project->getAccessGroups('view', 'User'));
        $this->assertSame(1, $project->getAccessGroups('create', 'User'));
        $this->assertSame(1, $project->getAccessGroups('editown', 'User'));
        $this->assertSame(0, $project->getAccessGroups('edit', 'User'));
        $this->assertSame(0, $project->getAccessGroups('manage', 'User'));

        // Custom groups
        $this->assertSame([5, 6], $project->getAccessGroups('edit'));
        $this->assertSame([5], $project->getAccessGroups('manage'));
        $this->assertSame([], $project->getAccessGroups('view'));
        $this->assertSame([], $project->getAccessGroups('create'));

        $sql = (string) $this->query;

        $this->assertStringContainsString('`#__accessgroups`', $sql);
        $this->assertStringContainsString('`project_id` = 12', $sql);
    }

    /**
     * Test that an existing project without groups is considered a fatal error.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testGetAccessGroupsWithoutGroupsThrows(): void
    {
        $this->db->method('setQuery')->willReturnSelf();
        $this->db->method('loadObjectList')->willReturn([]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No project groups defined.');

        (new TrackerProject($this->db, ['project_id' => 12]))->getAccessGroups('view', 'Public');
    }
}
