<?php

/**
 * Part of the Joomla! Tracker Package.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace JTracker\Tests\Command\Get\Project;

use Joomla\Github\Package\Issues\Milestones as MilestonesPackage;
use JTracker\Command\Get\Project\Milestones;
use JTracker\Tests\Command\CommandTestCase;

/**
 * Tests for the get:project_milestones command.
 *
 * @since  1.0
 */
class MilestonesTest extends CommandTestCase
{
    /**
     * Create a milestone object as returned by GitHub.
     *
     * @param   integer      $number  The milestone number.
     * @param   string       $title   The title.
     * @param   string       $state   The state.
     * @param   string|null  $dueOn   The due date.
     *
     * @return  object
     *
     * @since   1.0
     */
    private function milestone(int $number, string $title, string $state = 'open', ?string $dueOn = null): object
    {
        return (object) [
            'number'      => $number,
            'title'       => $title,
            'description' => 'Description of ' . $title,
            'state'       => $state,
            'due_on'      => $dueOn,
        ];
    }

    /**
     * Build a GitHub stub whose milestone API returns the given pages per state.
     *
     * @param   array  $pages  Pages keyed by state.
     *
     * @return  array  [GitHub stub, list of requests]
     *
     * @since   1.0
     */
    private function createGitHubWithMilestonePages(array $pages): array
    {
        $requested = new \ArrayObject();

        $milestones = $this->createMock(MilestonesPackage::class);
        $milestones->method('getList')
            ->willReturnCallback(
                static function ($user, $repo, $state, $sort, $direction, $page, $limit) use ($pages, $requested) {
                    $requested[] = [$state, $sort, $direction, $page, $limit];

                    return $pages[$state][$page - 1] ?? [];
                }
            );

        $github                     = $this->createGitHubStub();
        $github->issues->milestones = $milestones;

        return [$github, $requested];
    }

    /**
     * Open and closed milestones are fetched page by page until an empty page is returned.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testFetchesAllPagesOfBothStates(): void
    {
        [$github, $requested] = $this->createGitHubWithMilestonePages(
            [
                'open' => [
                    [$this->milestone(1, '4.0'), $this->milestone(2, '4.1', 'open', '2024-01-31T08:00:00Z')],
                    [$this->milestone(3, '4.2')],
                ],
                'closed' => [
                    [$this->milestone(4, '3.9', 'closed')],
                ],
            ]
        );

        $db = $this->createDatabaseMock();

        $this->assertSame(0, $this->runCommand(new Milestones(), ['--project' => 1], $db, $github));

        $this->assertSame(
            [
                ['open', 'due_date', 'desc', 1, 100],
                ['open', 'due_date', 'desc', 2, 100],
                ['open', 'due_date', 'desc', 3, 100],
                ['closed', 'due_date', 'desc', 1, 100],
                ['closed', 'due_date', 'desc', 2, 100],
            ],
            $requested->getArrayCopy()
        );

        $this->assertSame(
            [1, 2, 3, 4],
            array_map(static fn ($insert) => $insert[1]->milestone_number, $this->dbInserts)
        );

        $this->assertSame('2024-01-31 08:00:00', $this->dbInserts[1][1]->due_on);
        $this->assertNull($this->dbInserts[0][1]->due_on);
        $this->assertSame('closed', $this->dbInserts[3][1]->state);
    }

    /**
     * Known milestones are updated instead of inserted.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testUpdatesExistingMilestones(): void
    {
        [$github] = $this->createGitHubWithMilestonePages(['open' => [[$this->milestone(5, '4.4 renamed')]]]);

        $this->addDbResult(
            '/SELECT \*\s+FROM `#__tracker_milestones`/',
            [
                'milestone_id'     => 12,
                'milestone_number' => 5,
                'project_id'       => 1,
                'title'            => '4.4',
                'description'      => '',
                'state'            => 'open',
                'due_on'           => null,
            ]
        );

        $db = $this->createDatabaseMock();

        $this->runCommand(new Milestones(), ['--project' => 1], $db, $github);

        $this->assertSame([], $this->dbInserts);
        $this->assertCount(1, $this->dbUpdates);
        $this->assertSame(12, $this->dbUpdates[0][1]->milestone_id);
        $this->assertSame('4.4 renamed', $this->dbUpdates[0][1]->title);
    }

    /**
     * Milestones missing on GitHub are deleted using all orphan ids, the lookup excludes all quoted titles.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testDeletesOrphanMilestones(): void
    {
        [$github] = $this->createGitHubWithMilestonePages(
            [
                'open'   => [[$this->milestone(1, '4.0')]],
                'closed' => [[$this->milestone(2, "Dev's choice", 'closed')]],
            ]
        );

        $this->addDbResult('/SELECT milestone_id/', [3, 8, 11]);

        $db = $this->createDatabaseMock();

        $this->runCommand(new Milestones(), ['--project' => 1], $db, $github);

        $queries = $this->getDbQueries();

        $lookup = array_values(preg_grep('/SELECT milestone_id/', $queries));
        $this->assertCount(1, $lookup);
        $this->assertStringContainsString('`project_id` = 1', $lookup[0]);
        $this->assertStringContainsString("`title` NOT IN ('4.0', 'Dev\\'s choice')", $lookup[0]);

        $deletes = array_values(preg_grep('/^\s*DELETE/', $queries));
        $this->assertCount(1, $deletes);
        $this->assertStringContainsString('`#__tracker_milestones`', $deletes[0]);
        $this->assertStringContainsString('`milestone_id` IN (3, 8, 11)', $deletes[0]);
    }

    /**
     * When GitHub has no milestones, all milestones of the project are orphans.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testRepositoryWithoutMilestonesDeletesAllProjectMilestones(): void
    {
        [$github, $requested] = $this->createGitHubWithMilestonePages([]);

        $this->addDbResult('/SELECT milestone_id/', [4, 5]);

        $db = $this->createDatabaseMock();

        $this->runCommand(new Milestones(), ['--project' => 1], $db, $github);

        // One request per state
        $this->assertCount(2, $requested);

        $queries = $this->getDbQueries();

        $lookup = array_values(preg_grep('/SELECT milestone_id/', $queries));
        $this->assertCount(1, $lookup);
        $this->assertStringContainsString('`project_id` = 1', $lookup[0]);
        $this->assertStringNotContainsString('NOT IN', $lookup[0]);

        $deletes = array_values(preg_grep('/^\s*DELETE/', $queries));
        $this->assertCount(1, $deletes);
        $this->assertStringContainsString('`milestone_id` IN (4, 5)', $deletes[0]);
    }

    /**
     * Nothing is deleted when there are no orphans.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testDoesNotDeleteWithoutOrphans(): void
    {
        [$github] = $this->createGitHubWithMilestonePages(['open' => [[$this->milestone(1, '4.0')]]]);

        $db = $this->createDatabaseMock();

        $this->runCommand(new Milestones(), ['--project' => 1], $db, $github);

        $this->assertSame([], preg_grep('/^\s*DELETE/', $this->getDbQueries()));
    }
}
