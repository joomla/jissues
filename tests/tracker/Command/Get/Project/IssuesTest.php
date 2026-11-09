<?php

/**
 * Part of the Joomla! Tracker Package.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace JTracker\Tests\Command\Get\Project;

use JTracker\Command\Get\Project\Issues;
use JTracker\Github\Package\Issues as IssuesPackage;
use JTracker\Tests\Command\CommandTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for the progress handling of the get:project_issues command.
 *
 * The issues are kept outside of the selected range so the (database heavy) storage part is skipped.
 *
 * @since  1.0
 */
class IssuesTest extends CommandTestCase
{
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
        $pages = [
            [(object) ['number' => 3], (object) ['number' => 1]],
            [(object) ['number' => 2]],
        ];

        $requested = new \ArrayObject();

        $issues = $this->createStub(IssuesPackage::class);
        $issues->method('getListByRepository')
            ->willReturnCallback(
                static function ($user, $repo, $milestone, $state, $assignee, $mentioned, $labels, $sort, $direction, $since, $page, $limit) use ($pages, $requested) {
                    $requested[] = [$state, $page, $limit];

                    return $pages[$page - 1] ?? [];
                }
            );
        $issues->method('getRateLimitRemaining')->willReturn(5000);

        $github         = $this->createGitHubStub();
        $github->issues = $issues;

        $command = new Issues();
        $this->writeProperty($command, 'usePBar', $usePBar);
        $this->writeProperty($command, 'rangeFrom', 100);
        $this->writeProperty($command, 'rangeTo', 200);

        $exitCode = $this->runCommand($command, ['--project' => 1, '--status' => 'open'], $this->createDatabaseMock(), $github);

        $this->assertSame(0, $exitCode);
        $this->assertSame([['open', 1, 100], ['open', 2, 100], ['open', 3, 100]], $requested->getArrayCopy());

        // All pages are merged and sorted by number
        $this->assertSame([1, 2, 3], array_column($this->readProperty($command, 'issues'), 'number'));

        // Nothing has been changed
        $this->assertSame([], $command->getChangedIssueNumbers());

        $display = $this->output->fetch();

        if ($usePBar) {
            $this->assertStringContainsString('3/3', $display);
        } else {
            $this->assertStringContainsString('1...NiR', $display);
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
}
