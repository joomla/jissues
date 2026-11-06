<?php

/**
 * Part of the Joomla! Tracker Package.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace JTracker\Tests\Command\Get\Project;

use JTracker\Command\Get\Project\Labels;
use JTracker\Github\Package\Issues\Labels as LabelsPackage;
use JTracker\Tests\Command\CommandTestCase;

/**
 * Tests for the get:project_labels command.
 *
 * @since  1.0
 */
class LabelsTest extends CommandTestCase
{
    /**
     * Build a GitHub stub whose label API returns the given pages.
     *
     * @param   array  $pages  List of pages (arrays of label objects).
     *
     * @return  array  [GitHub stub, list of requested pages]
     *
     * @since   1.0
     */
    private function createGitHubWithLabelPages(array $pages): array
    {
        $requested = new \ArrayObject();

        $labels = $this->createMock(LabelsPackage::class);
        $labels->method('getList')
            ->willReturnCallback(
                static function ($owner, $repo, $page, $limit) use ($pages, $requested) {
                    $requested[] = [$owner, $repo, $page, $limit];

                    return $pages[$page - 1] ?? [];
                }
            );

        $github                 = $this->createGitHubStub();
        $github->issues->labels = $labels;

        return [$github, $requested];
    }

    /**
     * The command requests pages until an empty page is returned and processes the labels of all pages.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testFetchesAllPagesUntilAnEmptyPage(): void
    {
        [$github, $requested] = $this->createGitHubWithLabelPages(
            [
                [(object) ['name' => 'bug', 'color' => 'ff0000'], (object) ['name' => 'feature', 'color' => '00ff00']],
                [(object) ['name' => 'docs', 'color' => '0000ff']],
            ]
        );

        $db = $this->createDatabaseMock();

        $this->assertSame(0, $this->runCommand(new Labels(), ['--project' => 1], $db, $github));

        $this->assertSame(
            [
                ['joomla', 'joomla-cms', 1, 100],
                ['joomla', 'joomla-cms', 2, 100],
                ['joomla', 'joomla-cms', 3, 100],
            ],
            $requested->getArrayCopy()
        );

        // All labels of both pages are new and must be inserted
        $this->assertSame(['bug', 'feature', 'docs'], array_map(static fn ($insert) => $insert[1]->name, $this->dbInserts));

        foreach ($this->dbInserts as [$table, $row]) {
            $this->assertSame('#__tracker_labels', $table);
            $this->assertSame(1, $row->project_id);
        }
    }

    /**
     * Existing labels are only updated when their colour changed.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testUpdatesChangedLabelsOnly(): void
    {
        [$github] = $this->createGitHubWithLabelPages(
            [
                [(object) ['name' => 'bug', 'color' => 'ff0000'], (object) ['name' => 'feature', 'color' => '00ff00']],
            ]
        );

        $this->addDbResult(
            '/SELECT \*\s+FROM `#__tracker_labels`/',
            static function (string $sql) {
                if (strpos($sql, "`name` = 'bug'") !== false) {
                    return ['label_id' => 3, 'project_id' => 1, 'name' => 'bug', 'color' => 'ff0000'];
                }

                if (strpos($sql, "`name` = 'feature'") !== false) {
                    return ['label_id' => 4, 'project_id' => 1, 'name' => 'feature', 'color' => 'cccccc'];
                }

                return null;
            }
        );

        $db = $this->createDatabaseMock();

        $this->runCommand(new Labels(), ['--project' => 1], $db, $github);

        $this->assertSame([], $this->dbInserts);
        $this->assertCount(1, $this->dbUpdates);
        $this->assertSame(4, $this->dbUpdates[0][1]->label_id);
        $this->assertSame('00ff00', $this->dbUpdates[0][1]->color);
    }

    /**
     * Labels missing on GitHub are deleted using all orphan ids, the lookup excludes all quoted names.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testDeletesOrphanLabels(): void
    {
        [$github] = $this->createGitHubWithLabelPages(
            [
                [(object) ['name' => 'bug', 'color' => 'ff0000']],
                [(object) ['name' => "won't fix", 'color' => '000000']],
            ]
        );

        $this->addDbResult('/SELECT label_id/', [7, 9]);

        $db = $this->createDatabaseMock();

        $this->runCommand(new Labels(), ['--project' => 1], $db, $github);

        $queries = $this->getDbQueries();

        $lookup = preg_grep('/SELECT label_id/', $queries);
        $this->assertCount(1, $lookup);
        $lookup = reset($lookup);
        $this->assertStringContainsString('`project_id` = 1', $lookup);
        $this->assertStringContainsString("`name` NOT IN ('bug', 'won\\'t fix')", $lookup);

        $deletes = array_values(preg_grep('/^\s*DELETE/', $queries));
        $this->assertCount(1, $deletes);
        $this->assertStringContainsString('`#__tracker_labels`', $deletes[0]);
        $this->assertStringContainsString('`label_id` IN (7, 9)', $deletes[0]);
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
        [$github] = $this->createGitHubWithLabelPages([[(object) ['name' => 'bug', 'color' => 'ff0000']]]);

        $db = $this->createDatabaseMock();

        $this->runCommand(new Labels(), ['--project' => 1], $db, $github);

        $this->assertSame([], preg_grep('/^\s*DELETE/', $this->getDbQueries()));
    }

    /**
     * A repository without labels must not produce an invalid "NOT IN ()" clause.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testRepositoryWithoutLabels(): void
    {
        $this->markTestIncomplete('Labels::fetchData() builds "name NOT IN ()" when GitHub returns no labels (invalid SQL).');
    }
}
