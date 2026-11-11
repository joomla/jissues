<?php

/**
 * Part of the Joomla! Tracker Package.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace JTracker\Tests\App\Tracker\Controller\TestResult\Ajax;

use App\Projects\TrackerProject;
use App\Tracker\Controller\TestResult\Ajax\Submit;
use Joomla\Database\DatabaseDriver;
use Joomla\DI\Container;
use Joomla\Event\DispatcherInterface;
use Joomla\Input\Input;
use JTracker\Authentication\User;
use JTracker\Tests\Mocks\DatabaseMockTrait;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * Tests for \App\Tracker\Controller\TestResult\Ajax\Submit.
 *
 * @since  1.0
 */
class SubmitTest extends TestCase
{
    use DatabaseMockTrait;

    /**
     * Message of the exception thrown when the controller starts saving the test result.
     *
     * @var    string
     * @since  1.0
     */
    private const SAVE_REACHED = 'Saving the test result was reached.';

    /**
     * Run the controller for the given user and issue.
     *
     * @param   string          $username    The user name of the logged in user.
     * @param   \stdClass|null  $issue       The issue row returned by the database, null if not found.
     * @param   boolean         $maintainer  Whether the user has the manage right of the project.
     *
     * @return  \stdClass  The decoded AJAX response.
     *
     * @since   1.0
     */
    private function runController(string $username, ?\stdClass $issue, bool $maintainer = false): \stdClass
    {
        $db = $this->createDatabaseMock([]);
        $db->method('loadObject')->willReturn($issue);

        // The first query after the checks is the lookup of an existing test result in IssueModel::saveTest().
        $db->method('loadResult')->willThrowException(new \RuntimeException(self::SAVE_REACHED));

        $project = new TrackerProject(
            $db,
            (object) [
                'project_id'      => 3,
                'gh_project'      => 'no-listener',
                'gh_editbot_user' => '',
                'gh_editbot_pass' => '',
            ]
        );

        $user = new class ($project, $db) extends User {
            /**
             * Whether the user maintains the project.
             *
             * @var  boolean
             */
            public $maintainer = false;

            /**
             * Grant every action, "manage" only to maintainers.
             *
             * @param   string  $action  The action to check.
             *
             * @return  boolean
             */
            public function check($action)
            {
                return $action !== 'manage' || $this->maintainer;
            }
        };

        $user->id         = 1;
        $user->username   = $username;
        $user->maintainer = $maintainer;

        $application = new class ($user, $project, $this->createStub(DispatcherInterface::class)) {
            /**
             * The MIME type of the response.
             *
             * @var  string
             */
            public $mimeType = 'text/html';

            /**
             * The request input.
             *
             * @var  Input
             */
            private $input;

            /**
             * Constructor.
             *
             * @param   User                 $user        The logged in user.
             * @param   TrackerProject       $project     The active project.
             * @param   DispatcherInterface  $dispatcher  The event dispatcher.
             */
            public function __construct(private User $user, private TrackerProject $project, private DispatcherInterface $dispatcher)
            {
                $this->input = new Input(['issueId' => 12, 'result' => 1, 'sha' => 'abc123']);
            }

            /**
             * Get the logged in user.
             *
             * @return  User
             */
            public function getUser()
            {
                return $this->user;
            }

            /**
             * Get the active project.
             *
             * @return  TrackerProject
             */
            public function getProject()
            {
                return $this->project;
            }

            /**
             * Get the request input.
             *
             * @return  Input
             */
            public function getInput()
            {
                return $this->input;
            }

            /**
             * Get the event dispatcher.
             *
             * @return  DispatcherInterface
             */
            public function getDispatcher()
            {
                return $this->dispatcher;
            }

            /**
             * Get the logger.
             *
             * @return  NullLogger
             */
            public function getLogger()
            {
                return new NullLogger();
            }

            /**
             * Get a configuration value.
             *
             * @param   string  $key      The configuration key.
             * @param   mixed   $default  The default value.
             *
             * @return  mixed
             */
            public function get($key, $default = null)
            {
                return $default;
            }
        };

        $container = new Container();
        $container->set('app', $application);
        $container->set('JTracker\\Application\\Application', $application);
        $container->set(DatabaseDriver::class, $db);
        $container->alias('db', DatabaseDriver::class);

        $controller = new Submit();
        $controller->setContainer($container);

        return json_decode($controller->execute());
    }

    /**
     * Test that the author of an item can not submit a test result for it.
     *
     * @return  void
     *
     * @since   1.0
     */
    #[TestDox('The author of an item can not submit a test result for it')]
    public function testAuthorCanNotSubmitTestResult(): void
    {
        $response = $this->runController('author', (object) ['issue_number' => 42, 'opened_by' => 'author']);

        $this->assertSame('You can not submit a test result for an item you opened yourself.', $response->error);
        $this->assertEquals(new \stdClass(), $response->data);

        // Only the issue has been looked up, nothing has been written.
        $this->assertCount(1, $this->executedQueries);
        $this->assertStringContainsString('`opened_by`', $this->executedQueries[0]);
        $this->assertStringContainsString('`project_id` = 3', $this->executedQueries[0]);
    }

    /**
     * Test that another user passes the author check and the test result is saved.
     *
     * @return  void
     *
     * @since   1.0
     */
    #[TestDox('Another user can submit a test result for an item')]
    public function testOtherUserCanSubmitTestResult(): void
    {
        $response = $this->runController('tester', (object) ['issue_number' => 42, 'opened_by' => 'author']);

        $this->assertSame(self::SAVE_REACHED, $response->error);
        $this->assertStringContainsString('#__issues_tests', end($this->executedQueries));
    }

    /**
     * Test that a maintainer of the project can submit a test result for an item they opened.
     *
     * @return  void
     *
     * @since   1.0
     */
    #[TestDox('A maintainer of the project can submit a test result for an item they opened')]
    public function testMaintainerCanTestOwnItem(): void
    {
        $response = $this->runController('author', (object) ['issue_number' => 42, 'opened_by' => 'author'], true);

        $this->assertSame(self::SAVE_REACHED, $response->error);
        $this->assertStringContainsString('#__issues_tests', end($this->executedQueries));
    }

    /**
     * Test that an issue which does not belong to the project is rejected.
     *
     * @return  void
     *
     * @since   1.0
     */
    #[TestDox('An issue which is not found in the project is rejected')]
    public function testInvalidIssueIsRejected(): void
    {
        $response = $this->runController('tester', null);

        $this->assertSame('Invalid issue ID.', $response->error);
        $this->assertCount(1, $this->executedQueries);
    }
}
