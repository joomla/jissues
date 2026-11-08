<?php

/**
 * Part of the Joomla! Tracker Package.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace JTracker\Tests\Authentication;

use App\Projects\TrackerProject;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\Mysqli\MysqliQuery;
use JTracker\Authentication\Exception\AuthenticationException;
use JTracker\Authentication\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the permission checks of \JTracker\Authentication\User.
 *
 * @since  1.0
 */
class UserTest extends TestCase
{
    /**
     * The actions known to the project.
     *
     * @var    string[]
     * @since  1.0
     */
    private const ACTIONS = ['view', 'create', 'edit', 'editown', 'manage'];

    /**
     * Mocked database driver.
     *
     * @var    DatabaseDriver|MockObject
     * @since  1.0
     */
    private $db;

    /**
     * Sets up the fixture.
     *
     * @return  void
     *
     * @since   1.0
     */
    protected function setUp(): void
    {
        $this->db = $this->createMock(DatabaseDriver::class);
    }

    /**
     * Create a project mock with the given access map.
     *
     * @param   string[]  $public  Actions granted to the "Public" group.
     * @param   string[]  $user    Actions granted to the "User" group.
     * @param   array     $custom  Custom group ids per action.
     *
     * @return  TrackerProject|MockObject
     *
     * @since   1.0
     */
    private function createProject(array $public = [], array $user = [], array $custom = [])
    {
        $project = $this->createMock(TrackerProject::class);

        $project->method('getDefaultActions')
            ->willReturn(self::ACTIONS);

        $project->method('getAccessGroups')
            ->willReturnCallback(
                static function ($action, $filter = '') use ($public, $user, $custom) {
                    if ($filter === 'Public') {
                        return \in_array($action, $public, true) ? 1 : 0;
                    }

                    if ($filter === 'User') {
                        return \in_array($action, $user, true) ? 1 : 0;
                    }

                    return $custom[$action] ?? [];
                }
            );

        return $project;
    }

    /**
     * Create a concrete user object.
     *
     * @param   TrackerProject  $project       The project.
     * @param   integer         $id            The user id (0 = guest).
     * @param   array           $accessGroups  The groups the user is member of.
     *
     * @return  User
     *
     * @since   1.0
     */
    private function createUser(TrackerProject $project, int $id = 0, array $accessGroups = []): User
    {
        $user = new class ($project, $this->db) extends User {
            /**
             * Set the access groups of the user.
             *
             * @param   array  $groups  The group ids.
             *
             * @return  void
             */
            public function setAccessGroups(array $groups): void
            {
                $this->accessGroups = $groups;
            }

            /**
             * Expose loadAccessGroups().
             *
             * @return  $this
             */
            public function callLoadAccessGroups()
            {
                return $this->loadAccessGroups();
            }
        };

        $user->id       = $id;
        $user->username = $id ? 'user' . $id : '';
        $user->setAccessGroups($accessGroups);

        return $user;
    }

    /**
     * Assert that the authorization of an action is denied.
     *
     * @param   User    $user    The user.
     * @param   string  $action  The action.
     *
     * @return  void
     *
     * @since   1.0
     */
    private function assertDenied(User $user, string $action): void
    {
        try {
            $user->authorize($action);
        } catch (AuthenticationException $e) {
            $this->assertSame($action, $e->getAction());
            $this->assertSame($user, $e->getUser());
            $this->assertSame(403, $e->getCode());

            return;
        }

        $this->fail(\sprintf('Action "%s" was not denied.', $action));
    }

    /**
     * Test that a new user has no access groups.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testGetAccessGroupsIsEmptyByDefault(): void
    {
        $user = new class ($this->createProject(), $this->db) extends User {
        };

        $this->assertSame([], $user->getAccessGroups());
        $this->assertFalse($user->isAdmin);
    }

    /**
     * Test that loadAccessGroups() reads the group ids of the user from the database.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testLoadAccessGroups(): void
    {
        $query = new MysqliQuery($this->db);

        $this->db->method('getQuery')->willReturn($query);
        $this->db->method('quoteName')->willReturnCallback(
            static function ($name) {
                return '`' . $name . '`';
            }
        );
        $this->db->expects($this->once())
            ->method('setQuery')
            ->with($this->identicalTo($query))
            ->willReturnSelf();
        $this->db->expects($this->once())
            ->method('loadColumn')
            ->willReturn(['3', '7']);

        $user = $this->createUser($this->createProject(), 42);

        $this->assertSame($user, $user->callLoadAccessGroups());
        $this->assertSame(['3', '7'], $user->getAccessGroups());

        $sql = (string) $query;

        $this->assertStringContainsString('`#__user_accessgroup_map`', $sql);
        $this->assertStringContainsString('`group_id`', $sql);
        $this->assertStringContainsString('`user_id`=42', $sql);
    }

    /**
     * Test that the user id is cast to integer when loading the access groups.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testLoadAccessGroupsCastsUserIdToInteger(): void
    {
        $query = new MysqliQuery($this->db);

        $this->db->method('getQuery')->willReturn($query);
        $this->db->method('quoteName')->willReturnArgument(0);
        $this->db->method('setQuery')->willReturnSelf();
        $this->db->method('loadColumn')->willReturn([]);

        $user     = $this->createUser($this->createProject());
        $user->id = '5 OR 1=1';

        $user->callLoadAccessGroups();

        $this->assertStringContainsString('user_id=5', (string) $query);
        $this->assertStringNotContainsString('OR 1=1', (string) $query);
        $this->assertSame([], $user->getAccessGroups());
    }

    /**
     * Test that admin users are granted every action without consulting the project.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testAdminIsGrantedAllActions(): void
    {
        $project = $this->createMock(TrackerProject::class);
        $project->expects($this->never())->method('getAccessGroups');

        $user          = $this->createUser($project, 1);
        $user->isAdmin = true;

        $this->assertSame($user, $user->authorize('admin'));

        foreach (self::ACTIONS as $action) {
            $this->assertSame($user, $user->authorize($action));
            $this->assertTrue($user->check($action));
        }

        $this->assertTrue($user->canEditOwn('user1'));
    }

    /**
     * Test that the "admin" action is denied for a non admin user.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testAdminActionIsDeniedForNonAdmin(): void
    {
        $project = $this->createProject(self::ACTIONS, self::ACTIONS);
        $user    = $this->createUser($project, 1);

        $this->assertDenied($user, 'admin');
        $this->assertFalse($user->check('admin'));
    }

    /**
     * Test that the "admin" action is denied for a guest.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testAdminActionIsDeniedForGuest(): void
    {
        $user = $this->createUser($this->createProject(self::ACTIONS));

        $this->assertFalse($user->check('admin'));
        $this->assertDenied($user, 'admin');
    }

    /**
     * Test that an undefined action throws an exception.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testAuthorizeInvalidAction(): void
    {
        $user = $this->createUser($this->createProject(self::ACTIONS), 1);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Undefined action: foo');

        $user->authorize('foo');
    }

    /**
     * Test that check() does not swallow the exception for an undefined action.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testCheckInvalidAction(): void
    {
        $user = $this->createUser($this->createProject(self::ACTIONS), 1);

        $this->expectException(\InvalidArgumentException::class);

        $user->check('foo');
    }

    /**
     * Test that a guest is granted actions allowed for the "Public" group.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testGuestIsGrantedPublicAction(): void
    {
        $user = $this->createUser($this->createProject(['view']));

        $this->assertSame($user, $user->authorize('view'));
        $this->assertTrue($user->check('view'));
    }

    /**
     * Test that a guest is denied actions only allowed for the "User" group.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testGuestIsDeniedUserAction(): void
    {
        $user = $this->createUser($this->createProject(['view'], ['create']));

        $this->assertFalse($user->check('create'));
        $this->assertDenied($user, 'create');
    }

    /**
     * Test that a guest is denied actions allowed for a custom group, even if the group is set.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testGuestIsDeniedCustomGroupAction(): void
    {
        $user = $this->createUser($this->createProject([], [], ['manage' => [5]]), 0, [5]);

        $this->assertFalse($user->check('manage'));
    }

    /**
     * Test that a logged in user is granted actions allowed for the "Public" group.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testUserIsGrantedPublicAction(): void
    {
        $user = $this->createUser($this->createProject(['view']), 1);

        $this->assertTrue($user->check('view'));
    }

    /**
     * Test that a logged in user is granted actions allowed for the "User" group.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testUserIsGrantedUserAction(): void
    {
        $user = $this->createUser($this->createProject([], ['create']), 1);

        $this->assertSame($user, $user->authorize('create'));
        $this->assertTrue($user->check('create'));
    }

    /**
     * Test that a logged in user is denied actions not granted to any of his groups.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testUserIsDeniedUngrantedAction(): void
    {
        $user = $this->createUser($this->createProject(['view'], ['create'], ['manage' => [5]]), 1, [3]);

        $this->assertFalse($user->check('edit'));
        $this->assertFalse($user->check('manage'));
        $this->assertDenied($user, 'edit');
    }

    /**
     * Test that a member of a custom group is granted the actions of the group.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testCustomGroupMemberIsGranted(): void
    {
        $user = $this->createUser($this->createProject([], [], ['manage' => [4, 5]]), 1, [5]);

        $this->assertSame($user, $user->authorize('manage'));
        $this->assertTrue($user->check('manage'));
    }

    /**
     * Test that group ids loaded as strings from the database match integer ids of the project.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testCustomGroupMembershipWithStringIds(): void
    {
        $user = $this->createUser($this->createProject([], [], ['edit' => ['5']]), 1, ['5']);

        $this->assertTrue($user->check('edit'));
    }

    /**
     * Test that the membership in a custom group does not grant the actions of other groups.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testCustomGroupMemberIsDeniedOtherGroupAction(): void
    {
        $user = $this->createUser($this->createProject([], [], ['edit' => [4], 'manage' => [5]]), 1, [4]);

        $this->assertTrue($user->check('edit'));
        $this->assertFalse($user->check('manage'));
    }

    /**
     * Test that a granted permission is cached for the request.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testGrantedPermissionIsCached(): void
    {
        $project = $this->createMock(TrackerProject::class);
        $project->method('getDefaultActions')->willReturn(self::ACTIONS);
        $project->expects($this->once())
            ->method('getAccessGroups')
            ->with('view', 'Public')
            ->willReturn(1);

        $user = $this->createUser($project);

        $this->assertTrue($user->check('view'));
        $this->assertTrue($user->check('view'));
        $this->assertSame($user, $user->authorize('view'));
    }

    /**
     * Test that a denied permission is cached for the request.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testDeniedPermissionIsCached(): void
    {
        $project = $this->createMock(TrackerProject::class);
        $project->method('getDefaultActions')->willReturn(self::ACTIONS);

        // Public, User and custom groups are queried exactly once.
        $project->expects($this->exactly(3))
            ->method('getAccessGroups')
            ->willReturnCallback(
                static function ($action, $filter = '') {
                    return $filter ? 0 : [];
                }
            );

        $user = $this->createUser($project, 1);

        $this->assertFalse($user->check('manage'));
        $this->assertFalse($user->check('manage'));
        $this->assertDenied($user, 'manage');
    }

    /**
     * Test that the denied "admin" action is cached for the request.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testDeniedAdminActionIsCached(): void
    {
        $project = $this->createMock(TrackerProject::class);
        $project->expects($this->never())->method('getDefaultActions');
        $project->expects($this->never())->method('getAccessGroups');

        $user = $this->createUser($project, 1);

        $this->assertDenied($user, 'admin');
        $this->assertDenied($user, 'admin');
        $this->assertFalse($user->check('admin'));
    }

    /**
     * Test that the permission cache is kept per user object.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testPermissionCacheIsPerUser(): void
    {
        $project = $this->createProject([], ['edit']);

        $user  = $this->createUser($project, 1);
        $guest = $this->createUser($project);

        $this->assertTrue($user->check('edit'));
        $this->assertFalse($guest->check('edit'));
    }

    /**
     * Test canEditOwn() for the owner of an item.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testCanEditOwnForOwner(): void
    {
        $user = $this->createUser($this->createProject([], ['editown']), 1);

        $this->assertTrue($user->canEditOwn('user1'));
    }

    /**
     * Test canEditOwn() for an item owned by another user.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testCanEditOwnForOtherUser(): void
    {
        $user = $this->createUser($this->createProject([], ['editown']), 1);

        $this->assertFalse($user->canEditOwn('user2'));
    }

    /**
     * Test canEditOwn() for a user without the "editown" permission.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testCanEditOwnWithoutPermission(): void
    {
        $user = $this->createUser($this->createProject([], ['edit']), 1);

        $this->assertFalse($user->canEditOwn('user1'));
    }

    /**
     * Test canEditOwn() for a guest, whose empty user name must not match an item without owner.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testCanEditOwnForGuest(): void
    {
        $user = $this->createUser($this->createProject([], ['editown']));

        $this->assertFalse($user->canEditOwn(''));
    }
}
