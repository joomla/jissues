<?php

/**
 * Part of the Joomla! Tracker Package.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace JTracker\Tests\Command\Get\Project;

use JTracker\Command\Get\Project\Events;
use JTracker\Github\Package\Issues\Events as EventsPackage;
use JTracker\Tests\Command\CommandTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for the get:project_events command.
 *
 * @since  1.0
 */
class EventsTest extends CommandTestCase
{
    /**
     * Create an event object as returned by GitHub.
     *
     * @param   integer  $id     The event id.
     * @param   string   $event  The event type.
     * @param   array    $extra  Additional properties.
     *
     * @return  object
     *
     * @since   1.0
     */
    private function event(int $id, string $event, array $extra = []): object
    {
        return (object) array_merge(
            [
                'id'         => $id,
                'event'      => $event,
                'actor'      => (object) ['login' => 'alice'],
                'created_at' => '2024-02-01T10:00:00Z',
            ],
            $extra
        );
    }

    /**
     * Build a GitHub stub whose events API returns the given pages per issue.
     *
     * @param   array  $pages  Pages keyed by issue number.
     *
     * @return  array  [GitHub stub, list of requests]
     *
     * @since   1.0
     */
    private function createGitHubWithEventPages(array $pages): array
    {
        $requested = new \ArrayObject();

        $events = $this->createStub(EventsPackage::class);
        $events->method('getList')
            ->willReturnCallback(
                static function ($owner, $repo, $issue, $page, $limit) use ($pages, $requested) {
                    $requested[] = [$issue, $page, $limit];

                    return $pages[$issue][$page - 1] ?? [];
                }
            );
        $events->method('getRateLimitRemaining')->willReturn(5000);

        $github                 = $this->createGitHubStub();
        $github->issues->events = $events;

        return [$github, $requested];
    }

    /**
     * Create the command.
     *
     * @param   array    $issues   Changed issue numbers.
     * @param   boolean  $force    Force flag.
     * @param   boolean  $usePBar  Progress bar flag.
     *
     * @return  Events
     *
     * @since   1.0
     */
    private function createCommand(array $issues, bool $force = false, bool $usePBar = false): Events
    {
        $command = new Events();
        $command->setChangedIssueNumbers($issues);

        $this->writeProperty($command, 'force', $force);
        $this->writeProperty($command, 'usePBar', $usePBar);

        return $command;
    }

    /**
     * Events are fetched page by page per issue until an empty page is returned.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testFetchesAllPagesPerIssue(): void
    {
        [$github, $requested] = $this->createGitHubWithEventPages(
            [
                5 => [
                    [$this->event(1001, 'closed'), $this->event(1002, 'labeled')],
                    [$this->event(1003, 'reopened')],
                ],
            ]
        );

        $command = $this->createCommand([5, 7]);

        $this->assertSame(0, $this->runCommand($command, ['--project' => 1], $this->createDatabaseMock(), $github));

        $this->assertSame(
            [[5, 1, 100], [5, 2, 100], [5, 3, 100], [7, 1, 100]],
            $requested->getArrayCopy()
        );

        $items = $this->readProperty($command, 'items');
        $this->assertSame([1001, 1002, 1003], array_column($items[5], 'id'));
        $this->assertSame([], $items[7]);
    }

    /**
     * New events are inserted, ignored event types are skipped.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testInsertsNewEvents(): void
    {
        [$github] = $this->createGitHubWithEventPages(
            [
                5 => [
                    [
                        $this->event(1001, 'closed'),
                        $this->event(1002, 'labeled'),
                        $this->event(1003, 'renamed', ['rename' => (object) ['from' => 'Old', 'to' => 'New']]),
                    ],
                ],
            ]
        );

        $this->runCommand($this->createCommand([5]), ['--project' => 1], $this->createDatabaseMock(), $github);

        $this->assertSame([], $this->dbUpdates);
        $this->assertCount(2, $this->dbInserts);

        [$table, $closed] = $this->dbInserts[0];
        $this->assertSame('#__activities', $table);
        $this->assertSame(1001, $closed->gh_comment_id);
        $this->assertSame(5, $closed->issue_number);
        $this->assertSame(1, $closed->project_id);
        $this->assertSame('alice', $closed->user);
        $this->assertSame('close', $closed->event);
        $this->assertSame('2024-02-01 10:00:00', $closed->created_date);

        $renamed = $this->dbInserts[1][1];
        $this->assertSame('change', $renamed->event);
        $this->assertSame(
            [['name' => 'title', 'old' => 'Old', 'new' => 'New']],
            json_decode($renamed->text, true)
        );
    }

    /**
     * Without --force existing events are left alone.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testSkipsExistingEventsWithoutForce(): void
    {
        [$github] = $this->createGitHubWithEventPages([5 => [[$this->event(1001, 'closed')]]]);

        $this->addDbResult('/SELECT activities_id\s+FROM `#__activities`/', 42);

        $this->runCommand($this->createCommand([5]), ['--project' => 1], $this->createDatabaseMock(), $github);

        $this->assertSame([], $this->dbInserts);
        $this->assertSame([], $this->dbUpdates);
    }

    /**
     * With --force existing events are updated in place instead of inserted as duplicates.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testForceUpdatesExistingEvents(): void
    {
        [$github] = $this->createGitHubWithEventPages(
            [5 => [[$this->event(1001, 'assigned', ['assignee' => (object) ['login' => 'bob']])]]]
        );

        $this->addDbResult('/SELECT activities_id\s+FROM `#__activities`/', 42);
        $this->addDbResult(
            '/SELECT \*\s+FROM `#__activities`/',
            [
                'activities_id' => 42,
                'gh_comment_id' => 1001,
                'issue_number'  => 5,
                'project_id'    => 1,
                'user'          => 'alice',
                'event'         => 'assigned',
                'text'          => 'Assigned to carol',
                'text_raw'      => 'Assigned to carol',
                'created_date'  => '2024-01-01 00:00:00',
                'updated_date'  => '2024-01-01 00:00:00',
            ]
        );

        $this->runCommand($this->createCommand([5], true), ['--project' => 1], $this->createDatabaseMock(), $github);

        $this->assertSame([], $this->dbInserts);
        $this->assertCount(1, $this->dbUpdates);

        [$table, $row, $keys] = $this->dbUpdates[0];
        $this->assertSame('#__activities', $table);
        $this->assertSame(['activities_id'], $keys);
        $this->assertSame(42, $row->activities_id);
        $this->assertSame('Assigned to bob', $row->text_raw);
        $this->assertSame('2024-02-01 10:00:00', $row->created_date);

        // The existing row has been loaded by its primary key
        $this->assertCount(
            1,
            preg_grep("/SELECT \\*\\s+FROM `#__activities`\\s+WHERE `activities_id` = '42'/", $this->getDbQueries())
        );
    }

    /**
     * Passing --force to get:project must reach the events importer.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testForceOptionIsPropagated(): void
    {
        $this->markTestIncomplete('--force never reaches Events: Project::processEvents() does not pass it and Events::doExecute() does not read it.');
    }

    /**
     * Running with and without progress bar does not throw.
     *
     * @param   boolean  $usePBar  Progress bar flag.
     *
     * @return  void
     *
     * @since   1.0
     */
    #[DataProvider('progressBarProvider')]
    public function testRunsWithAndWithoutProgressBar(bool $usePBar): void
    {
        [$github] = $this->createGitHubWithEventPages([5 => [[$this->event(1001, 'closed')]]]);

        $command = $this->createCommand([5, 6], false, $usePBar);

        $this->assertSame(0, $this->runCommand($command, ['--project' => 1], $this->createDatabaseMock(), $github));
        $this->assertCount(1, $this->dbInserts);

        $display = $this->output->fetch();

        if ($usePBar) {
            $this->assertStringContainsString('2/2', $display);
        } else {
            $this->assertStringContainsString('1/2 - # 5:', $display);
            $this->assertStringContainsString('#5 (1/2)...+', $display);
        }
    }

    /**
     * Data provider for the progress bar flag.
     *
     * @return  array
     *
     * @since   1.0
     */
    public static function progressBarProvider(): array
    {
        return [
            'progress bar' => [true],
            'noprogress'   => [false],
        ];
    }

    /**
     * Nothing is fetched when no issue changed.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testDoesNothingWithoutChangedIssues(): void
    {
        [$github, $requested] = $this->createGitHubWithEventPages([]);

        $this->assertSame(0, $this->runCommand($this->createCommand([]), ['--project' => 1], $this->createDatabaseMock(), $github));

        $this->assertCount(0, $requested);
        $this->assertSame([], $this->dbInserts);
    }
}
