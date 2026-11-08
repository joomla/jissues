<?php

/**
 * Part of the Joomla Tracker
 *
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Tests\Hooks\Listeners;

use App\Projects\TrackerProject;
use App\Tracker\Controller\Hooks\Listeners\JoomlacmsPullsListener;
use App\Tracker\Table\IssuesTable;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseQuery;
use Joomla\DI\Container;
use Joomla\Event\Event;
use Joomla\Github\Github;
use Joomla\Github\Package\Issues;
use Joomla\Github\Package\Issues\Comments;
use Joomla\Github\Package\Issues\Labels;
use Joomla\Github\Package\Pulls;
use Joomla\Github\Package\Repositories;
use Joomla\Github\Package\Repositories\Statuses;
use Joomla\Http\Exception\InvalidResponseCodeException;
use JTracker\Github\DataType\Commit\Status;
use Monolog\Logger;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the JoomlacmsPullsListener and the AbstractListener helpers it uses.
 *
 * @since  1.0
 */
class JoomlacmsPullsListenerTest extends TestCase
{
    /**
     * Listener under test
     *
     * @var    JoomlacmsPullsListener
     * @since  1.0
     */
    private $listener;

    /**
     * Mocked GitHub object
     *
     * @var    Github|MockObject
     * @since  1.0
     */
    private $github;

    /**
     * Mocked GitHub issues package
     *
     * @var    Issues|MockObject
     * @since  1.0
     */
    private $issues;

    /**
     * Mocked GitHub issue labels package
     *
     * @var    Labels|MockObject
     * @since  1.0
     */
    private $labels;

    /**
     * Mocked GitHub issue comments package
     *
     * @var    Comments|MockObject
     * @since  1.0
     */
    private $comments;

    /**
     * Mocked GitHub pulls package
     *
     * @var    Pulls|MockObject
     * @since  1.0
     */
    private $pulls;

    /**
     * Mocked GitHub repository statuses package
     *
     * @var    Statuses|MockObject
     * @since  1.0
     */
    private $statuses;

    /**
     * Mocked logger
     *
     * @var    Logger|MockObject
     * @since  1.0
     */
    private $logger;

    /**
     * Mocked database driver
     *
     * @var    DatabaseDriver|MockObject
     * @since  1.0
     */
    private $db;

    /**
     * Project object
     *
     * @var    TrackerProject
     * @since  1.0
     */
    private $project;

    /**
     * Rows returned by the mocked database for `loadObjectList()`
     *
     * @var    array
     * @since  1.0
     */
    private $dbObjectList = [];

    /**
     * Objects passed to the mocked database `insertObject()` method, as [table, object] pairs
     *
     * @var    array
     * @since  1.0
     */
    private $insertedObjects = [];

    /**
     * Number of queries executed through the mocked database `execute()` method
     *
     * @var    integer
     * @since  1.0
     */
    private $executedQueries = 0;

    /**
     * Sets up the fixture.
     *
     * @return  void
     *
     * @since   1.0
     */
    protected function setUp(): void
    {
        $this->labels   = $this->createMock(Labels::class);
        $this->comments = $this->createMock(Comments::class);
        $this->pulls    = $this->createMock(Pulls::class);
        $this->statuses = $this->createMock(Statuses::class);

        $this->issues = $this->createMock(Issues::class);
        $this->issues->method('__get')
            ->willReturnMap(
                [
                    ['labels', $this->labels],
                    ['comments', $this->comments],
                ]
            );

        $repositories = $this->createMock(Repositories::class);
        $repositories->method('__get')
            ->willReturnMap([['statuses', $this->statuses]]);

        $this->github = $this->createMock(Github::class);
        $this->github->method('__get')
            ->willReturnMap(
                [
                    ['issues', $this->issues],
                    ['pulls', $this->pulls],
                    ['repositories', $repositories],
                ]
            );

        $this->logger = $this->createMock(Logger::class);
        $this->db     = $this->createDatabaseMock();

        $this->project = new TrackerProject(
            $this->db,
            [
                'project_id'      => 1,
                'gh_user'         => 'joomla',
                'gh_project'      => 'joomla-cms',
                'gh_editbot_user' => 'joomla-bot-test',
            ]
        );

        $container = new Container();
        $container->set('db', $this->db);

        $this->listener = new JoomlacmsPullsListener();
        $this->listener->setContainer($container);
    }

    /**
     * Builds a hook payload with the shape of GitHub's `pull_request` webhook.
     *
     * @param   array  $pullRequest  Values overriding the default pull request data
     *
     * @return  object
     *
     * @since   1.0
     */
    private function buildHookData(array $pullRequest = []): object
    {
        $data = [
            'action'       => 'opened',
            'number'       => 12345,
            'pull_request' => array_merge(
                [
                    'url'                => 'https://api.github.com/repos/joomla/joomla-cms/pulls/12345',
                    'id'                 => 987654321,
                    'html_url'           => 'https://github.com/joomla/joomla-cms/pull/12345',
                    'number'             => 12345,
                    'state'              => 'open',
                    'locked'             => false,
                    'title'              => 'Fix the article manager filters',
                    'body'               => "### Summary of Changes\n\nFixes the filters.\n\n### Testing Instructions\n\nApply the patch.",
                    'user'               => ['login' => 'octocat', 'id' => 1],
                    'author_association' => 'CONTRIBUTOR',
                    'draft'              => false,
                    'merged'             => false,
                    'head'               => ['label' => 'octocat:fix-filters', 'ref' => 'fix-filters', 'sha' => 'abc123'],
                    'base'               => ['label' => 'joomla:4.4-dev', 'ref' => '4.4-dev', 'sha' => 'def456'],
                ],
                $pullRequest
            ),
            'repository' => [
                'id'        => 2464908,
                'name'      => 'joomla-cms',
                'full_name' => 'joomla/joomla-cms',
                'owner'     => ['login' => 'joomla'],
            ],
            'sender' => ['login' => 'octocat', 'id' => 1],
        ];

        return json_decode(json_encode($data));
    }

    /**
     * Builds a list of changed files as returned by the GitHub API.
     *
     * @param   string  ...$filenames  The changed file names
     *
     * @return  array
     *
     * @since   1.0
     */
    private function buildFiles(string ...$filenames): array
    {
        $files = [];

        foreach ($filenames as $filename) {
            $files[] = (object) ['filename' => $filename, 'status' => 'modified', 'additions' => 1, 'deletions' => 1];
        }

        return $files;
    }

    /**
     * Configures the labels currently set on the mocked GitHub issue.
     *
     * @param   string  ...$labels  The label names
     *
     * @return  void
     *
     * @since   1.0
     */
    private function setExistingLabels(string ...$labels): void
    {
        $labelObjects = [];

        foreach ($labels as $label) {
            $labelObjects[] = (object) ['name' => $label, 'color' => 'ffffff'];
        }

        $this->issues->method('get')
            ->with('joomla', 'joomla-cms', 12345)
            ->willReturn((object) ['number' => 12345, 'labels' => $labelObjects]);
    }

    /**
     * Creates a mocked issues table returning the given values for its magic properties.
     *
     * @param   array  $data  The table data
     *
     * @return  IssuesTable|MockObject
     *
     * @since   1.0
     */
    private function createTable(array $data = []): IssuesTable
    {
        $data = array_merge(
            [
                'id'             => 42,
                'issue_number'   => 12345,
                'foreign_number' => null,
                'title'          => 'Fix the article manager filters',
                'status'         => 1,
            ],
            $data
        );

        $table = $this->createMock(IssuesTable::class);
        $table->method('__get')
            ->willReturnCallback(
                static function ($key) use ($data) {
                    return $data[$key] ?? null;
                }
            );

        return $table;
    }

    /**
     * Creates a database driver mock which never connects to a database.
     *
     * @return  DatabaseDriver|MockObject
     *
     * @since   1.0
     */
    private function createDatabaseMock(): DatabaseDriver
    {
        $db = $this->createMock(DatabaseDriver::class);

        $db->method('getQuery')
            ->willReturnCallback(
                function () {
                    $query = $this->createMock(DatabaseQuery::class);

                    foreach (['select', 'from', 'where', 'delete'] as $method) {
                        $query->method($method)->willReturnSelf();
                    }

                    return $query;
                }
            );

        $db->method('setQuery')->willReturnSelf();
        $db->method('quoteName')->willReturnArgument(0);
        $db->method('quote')->willReturnArgument(0);
        $db->method('getDateFormat')->willReturn('Y-m-d H:i:s');

        $columns = [];

        foreach (['id', 'activities_id', 'issue_id', 'category_id', 'project_id', 'issue_number', 'title', 'alias', 'color', 'event', 'user', 'text', 'created_date'] as $column) {
            $columns[$column] = (object) ['Field' => $column, 'Default' => null];
        }

        $db->method('getTableColumns')->willReturn($columns);

        $db->method('loadObjectList')
            ->willReturnCallback(
                function () {
                    return $this->dbObjectList;
                }
            );

        $db->method('loadObject')
            ->willReturn((object) ['id' => 1, 'title' => 'Category', 'color' => 'cccccc']);

        $db->method('insertObject')
            ->willReturnCallback(
                function ($table, $object) {
                    $this->insertedObjects[] = [$table, clone $object];

                    return true;
                }
            );

        $db->method('execute')
            ->willReturnCallback(
                function () {
                    $this->executedQueries++;

                    return true;
                }
            );

        return $db;
    }

    /**
     * Calls a protected method of the listener.
     *
     * @param   string  $method     The method name
     * @param   mixed   ...$args    The method arguments
     *
     * @return  mixed
     *
     * @since   1.0
     */
    private function invoke(string $method, ...$args)
    {
        $reflection = new \ReflectionMethod($this->listener, $method);
        $reflection->setAccessible(true);

        return $reflection->invoke($this->listener, ...$args);
    }

    /**
     * Creates a listener with the given protected methods replaced by mocks.
     *
     * @return  JoomlacmsPullsListener|MockObject
     *
     * @since   1.0
     */
    private function createDispatchListener(): JoomlacmsPullsListener
    {
        return $this->getMockBuilder(JoomlacmsPullsListener::class)
            ->onlyMethods(
                [
                    'checkPullLabels',
                    'updatePullTitle',
                    'checkPullBody',
                    'setPending',
                    'checkCategories',
                    'checkRTClabel',
                    'sendFirstTimeContributorMessage',
                ]
            )
            ->getMock();
    }

    /**
     * Builds the event the hook controller dispatches.
     *
     * @param   string  $name                The event name
     * @param   string  $action              The webhook action
     * @param   string  $authorAssociation   The author association of the pull request
     *
     * @return  Event
     *
     * @since   1.0
     */
    private function buildEvent(string $name, string $action, string $authorAssociation = 'CONTRIBUTOR'): Event
    {
        return new Event(
            $name,
            [
                'action'   => $action,
                'hookData' => $this->buildHookData(['author_association' => $authorAssociation]),
                'github'   => $this->github,
                'logger'   => $this->logger,
                'project'  => $this->project,
                'table'    => $this->createTable(),
            ]
        );
    }

    /**
     * @testdox  The listener subscribes to the pull request create and update events
     *
     * @return  void
     */
    public function testGetSubscribedEvents(): void
    {
        $this->assertSame(
            [
                'onPullAfterCreate' => 'onPullAfterCreate',
                'onPullAfterUpdate' => 'onPullAfterUpdate',
            ],
            JoomlacmsPullsListener::getSubscribedEvents()
        );
    }

    /**
     * Data provider for testOnPullAfterCreate
     *
     * @return  \Generator
     */
    public function onPullAfterCreateProvider(): \Generator
    {
        yield 'opened by a contributor' => ['opened', 'CONTRIBUTOR', true, false];
        yield 'opened by a first time contributor' => ['opened', 'FIRST_TIME_CONTRIBUTOR', true, true];
        yield 'opened by a first timer' => ['opened', 'FIRST_TIMER', true, true];
        yield 'redelivered by a first time contributor' => ['synchronize', 'FIRST_TIME_CONTRIBUTOR', false, true];
        yield 'redelivered by a member' => ['synchronize', 'MEMBER', false, false];
    }

    /**
     * @testdox  onPullAfterCreate runs the checks for opened pulls and greets first time contributors
     *
     * @param   string   $action             The webhook action
     * @param   string   $authorAssociation  The author association
     * @param   boolean  $runsChecks         Whether the opened checks should run
     * @param   boolean  $sendsGreeting      Whether the first time contributor message should be sent
     *
     * @return  void
     *
     * @dataProvider  onPullAfterCreateProvider
     */
    public function testOnPullAfterCreate(string $action, string $authorAssociation, bool $runsChecks, bool $sendsGreeting): void
    {
        $listener = $this->createDispatchListener();
        $calls    = $runsChecks ? 1 : 0;

        $listener->expects($this->exactly($calls))->method('checkPullLabels');
        $listener->expects($this->exactly($calls))->method('updatePullTitle');
        $listener->expects($this->exactly($calls))->method('checkPullBody');
        $listener->expects($this->exactly($calls))->method('setPending');
        $listener->expects($this->exactly($calls))->method('checkCategories');
        $listener->expects($this->never())->method('checkRTClabel');
        $listener->expects($sendsGreeting ? $this->once() : $this->never())->method('sendFirstTimeContributorMessage');

        $listener->onPullAfterCreate($this->buildEvent('onPullAfterCreate', $action, $authorAssociation));
    }

    /**
     * Data provider for testOnPullAfterUpdate
     *
     * @return  \Generator
     */
    public function onPullAfterUpdateProvider(): \Generator
    {
        // Action, checkPullBody calls, setPending calls, label/title/RTC/category check calls
        yield 'opened (redelivered)' => ['opened', 1, 1, 1];
        yield 'reopened' => ['reopened', 0, 1, 1];
        yield 'closed' => ['closed', 0, 0, 1];
        yield 'edited' => ['edited', 0, 0, 1];
        yield 'synchronize' => ['synchronize', 0, 0, 1];
        yield 'labeled' => ['labeled', 0, 0, 0];
        yield 'assigned' => ['assigned', 0, 0, 0];
    }

    /**
     * @testdox  onPullAfterUpdate runs the checks matching the webhook action
     *
     * @param   string   $action        The webhook action
     * @param   integer  $bodyChecks    Expected checkPullBody calls
     * @param   integer  $pendingCalls  Expected setPending calls
     * @param   integer  $otherChecks   Expected calls of the label, title, RTC and category checks
     *
     * @return  void
     *
     * @dataProvider  onPullAfterUpdateProvider
     */
    public function testOnPullAfterUpdate(string $action, int $bodyChecks, int $pendingCalls, int $otherChecks): void
    {
        $listener = $this->createDispatchListener();

        $listener->expects($this->exactly($bodyChecks))->method('checkPullBody');
        $listener->expects($this->exactly($pendingCalls))->method('setPending');
        $listener->expects($this->exactly($otherChecks))->method('checkPullLabels');
        $listener->expects($this->exactly($otherChecks))->method('updatePullTitle');
        $listener->expects($this->exactly($otherChecks))->method('checkRTClabel');
        $listener->expects($this->exactly($otherChecks))->method('checkCategories');
        $listener->expects($this->never())->method('sendFirstTimeContributorMessage');

        $listener->onPullAfterUpdate($this->buildEvent('onPullAfterUpdate', $action, 'FIRST_TIME_CONTRIBUTOR'));
    }

    /**
     * @testdox  onPullAfterCreate passes the event arguments to the checks
     *
     * @return  void
     */
    public function testOnPullAfterCreatePassesEventArguments(): void
    {
        $listener = $this->createDispatchListener();
        $event    = $this->buildEvent('onPullAfterCreate', 'opened');

        $listener->expects($this->once())
            ->method('checkPullLabels')
            ->with($event->getArgument('hookData'), $this->github, $this->logger, $this->project);

        $listener->expects($this->once())
            ->method('updatePullTitle')
            ->with($event->getArgument('hookData'), $this->github, $this->logger, $this->project, $event->getArgument('table'));

        $listener->expects($this->once())
            ->method('setPending')
            ->with($this->logger, $this->project, $event->getArgument('table'));

        $listener->onPullAfterCreate($event);
    }

    /**
     * Data provider for testCheckRtcLabel
     *
     * @return  \Generator
     */
    public function checkRtcLabelProvider(): \Generator
    {
        // Status, existing labels, expected added labels, expected removed label
        yield 'RTC status without label adds it' => [4, ['PR-4.4-dev'], ['RTC'], null];
        yield 'RTC status with label keeps it' => [4, ['RTC'], null, null];
        yield 'pending status with label removes it' => [3, ['PR-4.4-dev', 'RTC'], null, 'RTC'];
        yield 'pending status without label does nothing' => [3, [], null, null];
    }

    /**
     * @testdox  checkRTClabel keeps the RTC label in sync with the tracker status
     *
     * @param   integer      $status         The tracker status
     * @param   array        $existing       The labels set on GitHub
     * @param   array|null   $expectedAdd    The labels expected to be added
     * @param   string|null  $expectedRemove The label expected to be removed
     *
     * @return  void
     *
     * @dataProvider  checkRtcLabelProvider
     */
    public function testCheckRtcLabel(int $status, array $existing, ?array $expectedAdd, ?string $expectedRemove): void
    {
        $this->setExistingLabels(...$existing);

        if ($expectedAdd === null) {
            $this->labels->expects($this->never())->method('add');
        } else {
            $this->labels->expects($this->once())
                ->method('add')
                ->with('joomla', 'joomla-cms', 12345, $expectedAdd);
        }

        if ($expectedRemove === null) {
            $this->labels->expects($this->never())->method('removeFromIssue');
        } else {
            $this->labels->expects($this->once())
                ->method('removeFromIssue')
                ->with('joomla', 'joomla-cms', 12345, $expectedRemove);
        }

        $this->invoke('checkRTClabel', $this->buildHookData(), $this->github, $this->logger, $this->project, $this->createTable(['status' => $status]));
    }

    /**
     * @testdox  checkPullLabels adds the RFC, branch and file based labels
     *
     * @return  void
     */
    public function testCheckPullLabelsAddsLabels(): void
    {
        $this->setExistingLabels();

        $this->pulls->expects($this->once())
            ->method('getFiles')
            ->with('joomla', 'joomla-cms', 12345)
            ->willReturn($this->buildFiles('language/en-GB/joomla.ini', 'composer.json'));

        $this->labels->expects($this->once())
            ->method('add')
            ->with(
                'joomla',
                'joomla-cms',
                12345,
                ['RFC', 'PR-4.4-dev', 'Language Change', 'Composer Dependency Changed']
            );

        $this->labels->expects($this->never())->method('removeFromIssue');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Added 4 labels to joomla/joomla-cms #12345');

        $this->invoke(
            'checkPullLabels',
            $this->buildHookData(['title' => '[RFC] Use a new filter layout']),
            $this->github,
            $this->logger,
            $this->project
        );
    }

    /**
     * @testdox  checkPullLabels removes labels that no longer apply
     *
     * @return  void
     */
    public function testCheckPullLabelsRemovesLabels(): void
    {
        $this->setExistingLabels('RFC', 'PR-4.4-dev', 'NPM Resource Changed');

        $this->pulls->method('getFiles')
            ->willReturn($this->buildFiles('tests/Unit/Libraries/Cms/FooTest.php'));

        $this->labels->expects($this->once())
            ->method('add')
            ->with('joomla', 'joomla-cms', 12345, ['Unit/System Tests']);

        $removed = [];

        $this->labels->expects($this->exactly(2))
            ->method('removeFromIssue')
            ->willReturnCallback(
                function ($owner, $repo, $number, $label) use (&$removed) {
                    $this->assertSame(['joomla', 'joomla-cms', 12345], [$owner, $repo, $number]);
                    $removed[] = $label;

                    return true;
                }
            );

        $this->invoke('checkPullLabels', $this->buildHookData(), $this->github, $this->logger, $this->project);

        $this->assertSame(['RFC', 'NPM Resource Changed'], $removed);
    }

    /**
     * @testdox  checkPullLabels does not touch labels which are already correct
     *
     * @return  void
     */
    public function testCheckPullLabelsWithLabelsAlreadySet(): void
    {
        $this->setExistingLabels('PR-4.4-dev', 'NPM Resource Changed');

        $this->pulls->method('getFiles')
            ->willReturn($this->buildFiles('build/media_source/system/js/core.es6.js'));

        $this->labels->expects($this->never())->method('add');
        $this->labels->expects($this->never())->method('removeFromIssue');

        $this->invoke('checkPullLabels', $this->buildHookData(), $this->github, $this->logger, $this->project);
    }

    /**
     * @testdox  checkPullLabels does nothing more when the changed files cannot be retrieved
     *
     * @return  void
     */
    public function testCheckPullLabelsWithoutFiles(): void
    {
        $this->setExistingLabels();

        $exception = new \DomainException('API down');

        $this->pulls->method('getFiles')->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Error retrieving modified files for GitHub item joomla/joomla-cms #12345', ['exception' => $exception]);

        $this->labels->expects($this->never())->method('add');
        $this->labels->expects($this->never())->method('removeFromIssue');

        $this->invoke('checkPullLabels', $this->buildHookData(), $this->github, $this->logger, $this->project);
    }

    /**
     * Data provider for testCheckChange
     *
     * @return  \Generator
     */
    public function checkChangeProvider(): \Generator
    {
        yield 'language file' => [['administrator/language/en-GB/com_content.ini'], JoomlacmsPullsListener::CATEGORY_LANGUAGES, true];
        yield 'composer lock' => [['libraries/src/Foo.php', 'composer.lock'], JoomlacmsPullsListener::CATEGORY_COMPOSER, true];
        yield 'npm package file' => [['package.json'], JoomlacmsPullsListener::CATEGORY_NPM, true];
        yield 'unit tests' => [['tests/Unit/FooTest.php'], JoomlacmsPullsListener::CATEGORY_UNIT_TESTS, true];
        yield 'no matching file' => [['libraries/src/Foo.php'], JoomlacmsPullsListener::CATEGORY_LANGUAGES, false];
        yield 'no files' => [[], JoomlacmsPullsListener::CATEGORY_LANGUAGES, false];
    }

    /**
     * @testdox  checkChange detects changed files belonging to a category
     *
     * @param   array    $filenames  The changed file names
     * @param   integer  $category   The category id
     * @param   boolean  $expected   The expected result
     *
     * @return  void
     *
     * @dataProvider  checkChangeProvider
     */
    public function testCheckChange(array $filenames, int $category, bool $expected): void
    {
        $this->assertSame($expected, $this->invoke('checkChange', $this->buildFiles(...$filenames), $category));
    }

    /**
     * @testdox  setPending does not save an item which is already pending
     *
     * @return  void
     */
    public function testSetPendingWhenAlreadyPending(): void
    {
        $table = $this->createTable(['status' => 3]);
        $table->expects($this->never())->method('save');

        $this->invoke('setPending', $this->logger, $this->project, $table);
    }

    /**
     * @testdox  setPending resets the status to pending
     *
     * @return  void
     */
    public function testSetPending(): void
    {
        $table = $this->createTable(['status' => 4]);
        $table->expects($this->once())
            ->method('save')
            ->with(['status' => 3]);

        $this->logger->expects($this->never())->method('error');

        $this->invoke('setPending', $this->logger, $this->project, $table);
    }

    /**
     * Data provider for testSetPendingLogsErrors
     *
     * @return  \Generator
     */
    public function setPendingErrorProvider(): \Generator
    {
        yield 'invalid argument' => [new \InvalidArgumentException('Invalid')];
        yield 'runtime error' => [new \RuntimeException('Database down')];
    }

    /**
     * @testdox  setPending logs errors while saving the status
     *
     * @param   \Exception  $exception  The exception thrown by the table
     *
     * @return  void
     *
     * @dataProvider  setPendingErrorProvider
     */
    public function testSetPendingLogsErrors(\Exception $exception): void
    {
        $table = $this->createTable(['status' => 1]);
        $table->method('save')->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Error setting the status to pending in local application for GitHub pull request joomla/joomla-cms #12345',
                ['exception' => $exception]
            );

        $this->invoke('setPending', $this->logger, $this->project, $table);
    }

    /**
     * Data provider for testUpdatePullTitleWithoutChange
     *
     * @return  \Generator
     */
    public function updatePullTitleWithoutChangeProvider(): \Generator
    {
        yield 'title contains the id' => ['[#33456] Fix the article manager filters', 33456];
        yield 'no foreign number' => ['Fix the article manager filters', null];
    }

    /**
     * @testdox  updatePullTitle leaves the title alone when it has no foreign id to add
     *
     * @param   string        $title          The pull request title
     * @param   integer|null  $foreignNumber  The foreign number of the item
     *
     * @return  void
     *
     * @dataProvider  updatePullTitleWithoutChangeProvider
     */
    public function testUpdatePullTitleWithoutChange(string $title, ?int $foreignNumber): void
    {
        $table = $this->createTable(['foreign_number' => $foreignNumber]);
        $table->expects($this->never())->method('save');

        $this->pulls->expects($this->never())->method('edit');

        $this->invoke('updatePullTitle', $this->buildHookData(['title' => $title]), $this->github, $this->logger, $this->project, $table);
    }

    /**
     * @testdox  updatePullTitle prefixes the title with the foreign id on GitHub and locally
     *
     * @return  void
     */
    public function testUpdatePullTitle(): void
    {
        $table = $this->createTable(['foreign_number' => 33456]);
        $table->expects($this->once())
            ->method('save')
            ->with(['title' => '[#33456] Fix the article manager filters']);

        $this->pulls->expects($this->once())
            ->method('edit')
            ->with('joomla', 'joomla-cms', 12345, '[#33456] Fix the article manager filters');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Updated the title for GitHub pull request joomla/joomla-cms #12345');

        $this->invoke('updatePullTitle', $this->buildHookData(), $this->github, $this->logger, $this->project, $table);
    }

    /**
     * Data provider for GitHub API errors
     *
     * @return  \Generator
     */
    public function gitHubErrorProvider(): \Generator
    {
        yield 'invalid response code' => [new InvalidResponseCodeException('Not Found', 404)];
        yield 'domain exception' => [new \DomainException('Invalid response')];
    }

    /**
     * @testdox  updatePullTitle does not change the local title when GitHub fails
     *
     * @param   \Exception  $exception  The exception thrown by the GitHub API
     *
     * @return  void
     *
     * @dataProvider  gitHubErrorProvider
     */
    public function testUpdatePullTitleGitHubError(\Exception $exception): void
    {
        $table = $this->createTable(['foreign_number' => 33456]);
        $table->expects($this->never())->method('save');

        $this->pulls->method('edit')->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Error updating the title for GitHub pull request joomla/joomla-cms #12345', ['exception' => $exception]);

        $this->invoke('updatePullTitle', $this->buildHookData(), $this->github, $this->logger, $this->project, $table);
    }

    /**
     * @testdox  checkPullBody does nothing for pull requests with a description
     *
     * @return  void
     */
    public function testCheckPullBodyWithDescription(): void
    {
        $this->issues->expects($this->never())->method('get');
        $this->labels->expects($this->never())->method('add');
        $this->comments->expects($this->never())->method('create');

        $this->invoke('checkPullBody', $this->buildHookData(), $this->github, $this->logger, $this->project);
    }

    /**
     * @testdox  checkPullBody labels and comments on pull requests without a description
     *
     * @return  void
     */
    public function testCheckPullBodyWithoutDescription(): void
    {
        $this->setExistingLabels('PR-4.4-dev');

        $this->labels->expects($this->once())
            ->method('add')
            ->with('joomla', 'joomla-cms', 12345, ['Test instructions missing']);

        $this->comments->expects($this->once())
            ->method('create')
            ->with(
                'joomla',
                'joomla-cms',
                12345,
                $this->logicalAnd(
                    $this->stringStartsWith('Please add more information to your issue.'),
                    $this->stringContains('This is an automated message from the <a href="https://github.com/joomla/jissues">J!Tracker Application</a>')
                )
            );

        $this->logger->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Added 1 labels to joomla/joomla-cms #12345'],
                ['Added a no description comment to joomla/joomla-cms #12345']
            );

        $this->invoke('checkPullBody', $this->buildHookData(['body' => '']), $this->github, $this->logger, $this->project);
    }

    /**
     * @testdox  checkPullBody only comments when the test instructions label is already set
     *
     * @return  void
     */
    public function testCheckPullBodyWithLabelAlreadySet(): void
    {
        $this->setExistingLabels('Test instructions missing');

        $this->labels->expects($this->never())->method('add');
        $this->comments->expects($this->once())->method('create');

        $this->invoke('checkPullBody', $this->buildHookData(['body' => null]), $this->github, $this->logger, $this->project);
    }

    /**
     * @testdox  checkPullBody logs errors while posting the comment
     *
     * @param   \Exception  $exception  The exception thrown by the GitHub API
     *
     * @return  void
     *
     * @dataProvider  gitHubErrorProvider
     */
    public function testCheckPullBodyGitHubError(\Exception $exception): void
    {
        $this->setExistingLabels('Test instructions missing');

        $this->comments->method('create')->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Error posting comment to GitHub pull request joomla/joomla-cms #12345', ['exception' => $exception]);

        $this->invoke('checkPullBody', $this->buildHookData(['body' => '']), $this->github, $this->logger, $this->project);
    }

    /**
     * @testdox  checkCategories stores the categories matching the changed files and keeps manual categories
     *
     * @return  void
     */
    public function testCheckCategories(): void
    {
        // Category 99 is not handled by the tracker, category 24 no longer matches the changed files
        $this->dbObjectList = [(object) ['category_id' => 99], (object) ['category_id' => 24]];

        $this->pulls->method('getFiles')
            ->willReturn($this->buildFiles('administrator/modules/mod_menu/mod_menu.php', 'package.json'));

        $this->invoke('checkCategories', $this->buildHookData(), $this->github, $this->logger, $this->project, $this->createTable());

        // The no longer matching category is deleted
        $this->assertSame(1, $this->executedQueries);

        $mappings = [];
        $activity = null;

        foreach ($this->insertedObjects as [$tableName, $object]) {
            if ($tableName === '#__issue_category_map') {
                $mappings[] = [$object->issue_id, $object->category_id];
            } elseif ($tableName === '#__activities') {
                $activity = $object;
            }
        }

        $this->assertSame(
            [
                [42, JoomlacmsPullsListener::CATEGORY_MODULES],
                [42, JoomlacmsPullsListener::CATEGORY_ADMINISTRATION],
                [42, JoomlacmsPullsListener::CATEGORY_NPM],
            ],
            $mappings
        );

        $this->assertNotNull($activity, 'The category change should be recorded as activity');
        $this->assertSame('joomla-bot-test', $activity->user);
        $this->assertSame(12345, $activity->issue_number);
        $this->assertSame(1, $activity->project_id);
    }

    /**
     * @testdox  checkCategories does not write anything when the categories are unchanged
     *
     * @return  void
     */
    public function testCheckCategoriesUnchanged(): void
    {
        $this->dbObjectList = [(object) ['category_id' => 23], (object) ['category_id' => 13]];

        $this->pulls->method('getFiles')
            ->willReturn($this->buildFiles('administrator/modules/mod_menu/mod_menu.php'));

        $this->db->expects($this->never())->method('insertObject');
        $this->db->expects($this->never())->method('execute');

        $this->invoke('checkCategories', $this->buildHookData(), $this->github, $this->logger, $this->project, $this->createTable());
    }

    /**
     * @testdox  checkCategories does nothing when the changed files cannot be retrieved
     *
     * @return  void
     */
    public function testCheckCategoriesWithoutFiles(): void
    {
        $this->dbObjectList = [(object) ['category_id' => 23]];

        $this->pulls->method('getFiles')->willReturn([]);

        // Only the current categories are loaded
        $this->db->expects($this->once())->method('loadObjectList');
        $this->db->expects($this->never())->method('insertObject');
        $this->db->expects($this->never())->method('execute');

        $this->invoke('checkCategories', $this->buildHookData(), $this->github, $this->logger, $this->project, $this->createTable());
    }

    /**
     * @testdox  sendFirstTimeContributorMessage posts the welcome message as a comment
     *
     * @return  void
     */
    public function testSendFirstTimeContributorMessage(): void
    {
        $this->comments->expects($this->once())
            ->method('create')
            ->with(
                'joomla',
                'joomla-cms',
                12345,
                $this->logicalAnd(
                    $this->stringStartsWith('Thank you so much for spending time creating your first pull request for Joomla!'),
                    $this->stringEndsWith('<br />*This is an automated message from the <a href="https://github.com/joomla/jissues">J!Tracker Application</a>.*')
                )
            );

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Added a comment by the bot to joomla/joomla-cms #12345');

        $this->invoke('sendFirstTimeContributorMessage', $this->buildHookData(), $this->github, $this->logger, $this->project);
    }

    /**
     * @testdox  sendCommentAsBotUser logs errors while posting the comment
     *
     * @param   \Exception  $exception  The exception thrown by the GitHub API
     *
     * @return  void
     *
     * @dataProvider  gitHubErrorProvider
     */
    public function testSendCommentAsBotUserGitHubError(\Exception $exception): void
    {
        $this->comments->method('create')->willThrowException($exception);

        $this->logger->expects($this->never())->method('info');
        $this->logger->expects($this->once())
            ->method('error')
            ->with('Error posting comment to GitHub pull request joomla/joomla-cms #12345', ['exception' => $exception]);

        $this->invoke('sendCommentAsBotUser', $this->buildHookData(), $this->github, $this->logger, $this->project, 'Hello');
    }

    /**
     * @testdox  getIssueNumber reads the number from pull request or issue payloads
     *
     * @return  void
     */
    public function testGetIssueNumber(): void
    {
        $this->assertSame(12345, $this->invoke('getIssueNumber', $this->buildHookData()));
        $this->assertSame(678, $this->invoke('getIssueNumber', json_decode('{"issue": {"number": 678}}')));
        $this->assertNull($this->invoke('getIssueNumber', json_decode('{"sender": {"login": "octocat"}}')));
    }

    /**
     * @testdox  checkLabel checks the labels of an issue payload
     *
     * @return  void
     */
    public function testCheckLabelWithIssuePayload(): void
    {
        $this->issues->expects($this->exactly(2))
            ->method('get')
            ->with('joomla', 'joomla-cms', 678)
            ->willReturn((object) ['labels' => [(object) ['name' => 'Information Required']]]);

        $hookData = json_decode('{"issue": {"number": 678}}');

        $this->assertTrue($this->invoke('checkLabel', $hookData, $this->github, $this->logger, $this->project, 'Information Required'));
        $this->assertFalse($this->invoke('checkLabel', $hookData, $this->github, $this->logger, $this->project, 'RTC'));
    }

    /**
     * Data provider for the label helpers without issue number
     *
     * @return  \Generator
     */
    public function labelHelperProvider(): \Generator
    {
        yield 'checkLabel' => ['checkLabel', 'RTC'];
        yield 'addLabels' => ['addLabels', ['RTC']];
        yield 'removeLabels' => ['removeLabels', ['RTC']];
    }

    /**
     * @testdox  The label helpers fail when the payload has no issue number
     *
     * @param   string  $method  The helper method
     * @param   mixed   $labels  The label argument
     *
     * @return  void
     *
     * @dataProvider  labelHelperProvider
     */
    public function testLabelHelpersWithoutIssueNumber(string $method, $labels): void
    {
        $this->logger->expects($this->once())
            ->method('error')
            ->with('Error retrieving issue number for joomla/joomla-cms');

        $this->issues->expects($this->never())->method('get');
        $this->labels->expects($this->never())->method($this->anything());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Error retrieving issue number for joomla/joomla-cms');

        $this->invoke($method, json_decode('{}'), $this->github, $this->logger, $this->project, $labels);
    }

    /**
     * @testdox  checkLabel converts GitHub errors into runtime exceptions
     *
     * @param   \Exception  $exception  The exception thrown by the GitHub API
     *
     * @return  void
     *
     * @dataProvider  gitHubErrorProvider
     */
    public function testCheckLabelGitHubError(\Exception $exception): void
    {
        $this->issues->method('get')->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Error retrieving labels for GitHub item joomla/joomla-cms #12345', ['exception' => $exception]);

        try {
            $this->invoke('checkLabel', $this->buildHookData(), $this->github, $this->logger, $this->project, 'RTC');

            $this->fail('A RuntimeException should be thrown');
        } catch (\RuntimeException $e) {
            $this->assertSame($exception, $e->getPrevious());
            $this->assertSame($exception->getMessage(), $e->getMessage());
        }
    }

    /**
     * @testdox  addLabels does not call GitHub without labels
     *
     * @return  void
     */
    public function testAddLabelsWithoutLabels(): void
    {
        $this->labels->expects($this->never())->method('add');

        $this->invoke('addLabels', $this->buildHookData(), $this->github, $this->logger, $this->project, []);
    }

    /**
     * @testdox  addLabels converts GitHub errors into runtime exceptions
     *
     * @param   \Exception  $exception  The exception thrown by the GitHub API
     *
     * @return  void
     *
     * @dataProvider  gitHubErrorProvider
     */
    public function testAddLabelsGitHubError(\Exception $exception): void
    {
        $this->labels->method('add')->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Error adding labels to GitHub pull request joomla/joomla-cms #12345', ['exception' => $exception]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage($exception->getMessage());

        $this->invoke('addLabels', $this->buildHookData(), $this->github, $this->logger, $this->project, ['RTC']);
    }

    /**
     * @testdox  removeLabels does not call GitHub without labels
     *
     * @return  void
     */
    public function testRemoveLabelsWithoutLabels(): void
    {
        $this->labels->expects($this->never())->method('removeFromIssue');

        $this->invoke('removeLabels', $this->buildHookData(), $this->github, $this->logger, $this->project, []);
    }

    /**
     * @testdox  removeLabels converts GitHub errors into runtime exceptions
     *
     * @param   \Exception  $exception  The exception thrown by the GitHub API
     *
     * @return  void
     *
     * @dataProvider  gitHubErrorProvider
     */
    public function testRemoveLabelsGitHubError(\Exception $exception): void
    {
        $this->labels->expects($this->once())
            ->method('removeFromIssue')
            ->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Error removing the RTC label from GitHub pull request joomla/joomla-cms #12345', ['exception' => $exception]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage($exception->getMessage());

        $this->invoke('removeLabels', $this->buildHookData(), $this->github, $this->logger, $this->project, ['RTC', 'RFC']);
    }

    /**
     * @testdox  getChangedFilesByPullRequest returns an empty list on GitHub errors
     *
     * @param   \Exception  $exception  The exception thrown by the GitHub API
     *
     * @return  void
     *
     * @dataProvider  gitHubErrorProvider
     */
    public function testGetChangedFilesByPullRequestGitHubError(\Exception $exception): void
    {
        $this->pulls->method('getFiles')->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Error retrieving modified files for GitHub item joomla/joomla-cms #12345', ['exception' => $exception]);

        $this->assertSame([], $this->invoke('getChangedFilesByPullRequest', $this->buildHookData(), $this->github, $this->logger, $this->project));
    }

    /**
     * @testdox  createStatus uses the given commit SHA
     *
     * @return  void
     */
    public function testCreateStatusWithSha(): void
    {
        $status              = new Status();
        $status->state       = 'success';
        $status->targetUrl   = 'https://issues.joomla.org/tracker/joomla-cms/12345';
        $status->description = 'Human Test Results: 2 Successful 0 Failed.';
        $status->context     = 'JTracker/HumanTestResults';

        $result = (object) ['id' => 1, 'state' => 'success'];

        $this->pulls->expects($this->never())->method('get');

        $this->statuses->expects($this->once())
            ->method('create')
            ->with('joomla', 'joomla-cms', 'abc123', 'success', $status->targetUrl, $status->description, $status->context)
            ->willReturn($result);

        $this->assertSame($result, $this->invoke('createStatus', $this->github, $this->project, 12345, $status, 'abc123'));
    }

    /**
     * @testdox  createStatus looks up the SHA of the pull request head when none is given
     *
     * @return  void
     */
    public function testCreateStatusWithoutSha(): void
    {
        $status        = new Status();
        $status->state = 'pending';

        $this->pulls->expects($this->once())
            ->method('get')
            ->with('joomla', 'joomla-cms', 12345)
            ->willReturn((object) ['head' => (object) ['sha' => 'fedcba']]);

        $this->statuses->expects($this->once())
            ->method('create')
            ->with('joomla', 'joomla-cms', 'fedcba', 'pending', '', '', '');

        $this->invoke('createStatus', $this->github, $this->project, 12345, $status);
    }

    /**
     * @testdox  getGithubBotName returns the configured bot account or the fallback
     *
     * @return  void
     */
    public function testGetGithubBotName(): void
    {
        $this->assertSame('joomla-bot-test', $this->invoke('getGithubBotName', $this->project));

        $project = new TrackerProject($this->db, ['gh_user' => 'joomla', 'gh_project' => 'joomla-cms']);

        $this->assertSame('joomla-bot', $this->invoke('getGithubBotName', $project));
    }

    /**
     * @testdox  setCategories fails when the payload has no issue number
     *
     * @return  void
     */
    public function testSetCategoriesWithoutIssueNumber(): void
    {
        $this->logger->expects($this->once())
            ->method('error')
            ->with('Error retrieving issue number for joomla/joomla-cms');

        $this->db->expects($this->never())->method('loadObjectList');

        $this->expectException(\RuntimeException::class);

        $this->invoke('setCategories', json_decode('{}'), $this->logger, $this->project, $this->createTable(), [23]);
    }

    /**
     * @testdox  getCategories returns the category ids of the issue
     *
     * @return  void
     */
    public function testGetCategories(): void
    {
        $this->dbObjectList = [(object) ['category_id' => 23], (object) ['category_id' => 99]];

        $this->assertSame([23, 99], $this->invoke('getCategories', $this->createTable()));
    }
}
