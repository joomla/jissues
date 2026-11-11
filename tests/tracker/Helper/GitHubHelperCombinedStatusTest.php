<?php

/**
 * Part of the Joomla! Tracker Package.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace JTracker\Tests\Helper;

use App\Projects\TrackerProject;
use Joomla\Http\Exception\UnexpectedResponseException;
use Joomla\Http\Response;
use JTracker\Github\Package\Repositories\Statuses;
use JTracker\Helper\GitHubHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

/**
 * Tests for GitHubHelper::getCombinedStatus().
 *
 * @since  1.0
 */
class GitHubHelperCombinedStatusTest extends TestCase
{
    /**
     * Run getCombinedStatus() against the given API answers.
     *
     * @param   array        $statuses   The commit statuses of the combined status.
     * @param   string       $state      The state of the combined status.
     * @param   array|null   $checkRuns  The check runs, null if the checks API answers with an error.
     *
     * @return  \JTracker\Github\DataType\Commit\CombinedStatus
     */
    private function getCombinedStatus(array $statuses, string $state, ?array $checkRuns)
    {
        $api = $this->createStub(Statuses::class);
        $api->method('getCombined')->willReturn((object) ['state' => $state, 'statuses' => $statuses]);

        if ($checkRuns === null) {
            $api->method('getCheckRuns')->willThrowException(
                new UnexpectedResponseException(new Response('php://memory', 403), 'Resource not accessible by integration', 403)
            );
        } else {
            $api->method('getCheckRuns')->willReturn((object) ['total_count' => \count($checkRuns), 'check_runs' => $checkRuns]);
        }

        $gitHub  = (object) ['repositories' => (object) ['statuses' => $api]];
        $project = $this->createStub(TrackerProject::class);

        return (new GitHubHelper($gitHub))->getCombinedStatus($project, str_repeat('a', 40));
    }

    /**
     * Build a commit status as GitHub returns it.
     *
     * @param   string  $state    The state.
     * @param   string  $context  The context.
     *
     * @return  object
     */
    private static function commitStatus(string $state, string $context = 'ci/external'): object
    {
        return (object) [
            'state'       => $state,
            'target_url'  => 'https://ci.example.org/1',
            'description' => 'The build ' . $state,
            'context'     => $context,
        ];
    }

    /**
     * Build a check run as GitHub returns it.
     *
     * @param   string       $status      The status (queued, in_progress, completed).
     * @param   string|null  $conclusion  The conclusion of a completed run.
     * @param   string       $name        The name.
     * @param   string|null  $title       The output title.
     *
     * @return  object
     */
    private static function checkRun(string $status, ?string $conclusion = null, string $name = 'Run Unit tests', ?string $title = null): object
    {
        return (object) [
            'name'       => $name,
            'status'     => $status,
            'conclusion' => $conclusion,
            'html_url'   => 'https://github.com/joomla/jissues/actions/runs/1/job/2',
            'output'     => (object) ['title' => $title, 'summary' => null],
        ];
    }

    /**
     * @return  void
     */
    #[TestDox('Without check runs the combined commit status is used as before')]
    public function testCommitStatusesOnly(): void
    {
        $combined = $this->getCombinedStatus([self::commitStatus('success')], 'success', []);

        $this->assertSame('success', $combined->state);
        $this->assertCount(1, $combined->statuses);
        $this->assertSame('ci/external', $combined->statuses[0]->context);
        $this->assertSame('https://ci.example.org/1', $combined->statuses[0]->targetUrl);
        $this->assertSame('The build success', $combined->statuses[0]->description);
    }

    /**
     * @return  void
     */
    #[TestDox('Check runs are added as statuses')]
    public function testCheckRunsAreAdded(): void
    {
        $combined = $this->getCombinedStatus(
            [],
            'pending',
            [self::checkRun('completed', 'success', 'Run PHPStan', 'No errors'), self::checkRun('in_progress', null, 'Run Unit tests (PHP 8.4)')]
        );

        $this->assertCount(2, $combined->statuses);

        $this->assertSame('success', $combined->statuses[0]->state);
        $this->assertSame('Run PHPStan', $combined->statuses[0]->context);
        $this->assertSame('No errors', $combined->statuses[0]->description);
        $this->assertSame('https://github.com/joomla/jissues/actions/runs/1/job/2', $combined->statuses[0]->targetUrl);

        $this->assertSame('pending', $combined->statuses[1]->state);
        $this->assertSame('In progress', $combined->statuses[1]->description);
    }

    /**
     * Data provider for the combined state.
     *
     * @return  array
     */
    public static function stateProvider(): array
    {
        return [
            'all check runs passed, no commit statuses' => [
                [], 'pending', [self::checkRun('completed', 'success'), self::checkRun('completed', 'skipped')], 'success',
            ],
            'a check run is still running' => [
                [], 'pending', [self::checkRun('completed', 'success'), self::checkRun('queued')], 'pending',
            ],
            'a check run failed' => [
                [], 'pending', [self::checkRun('completed', 'failure'), self::checkRun('in_progress')], 'failure',
            ],
            'a check run was cancelled' => [
                [], 'pending', [self::checkRun('completed', 'cancelled')], 'failure',
            ],
            'a check run timed out' => [
                [], 'pending', [self::checkRun('completed', 'timed_out')], 'failure',
            ],
            'neutral counts as passed' => [
                [], 'pending', [self::checkRun('completed', 'neutral')], 'success',
            ],
            'a commit status failed, the check runs passed' => [
                [self::commitStatus('failure')], 'failure', [self::checkRun('completed', 'success')], 'failure',
            ],
            'a commit status errored' => [
                [self::commitStatus('error')], 'error', [self::checkRun('completed', 'success')], 'failure',
            ],
            'a commit status is pending, the check runs passed' => [
                [self::commitStatus('pending')], 'pending', [self::checkRun('completed', 'success')], 'pending',
            ],
            'everything passed' => [
                [self::commitStatus('success')], 'success', [self::checkRun('completed', 'success')], 'success',
            ],
        ];
    }

    /**
     * @param   array   $statuses   The commit statuses.
     * @param   string  $state      The state of the combined commit status.
     * @param   array   $checkRuns  The check runs.
     * @param   string  $expected   The expected state.
     *
     * @return  void
     */
    #[DataProvider('stateProvider')]
    #[TestDox('Combined state: $_dataName')]
    public function testCombinedState(array $statuses, string $state, array $checkRuns, string $expected): void
    {
        $this->assertSame($expected, $this->getCombinedStatus($statuses, $state, $checkRuns)->state);
    }

    /**
     * @return  void
     */
    #[TestDox('An error of the checks API leaves the commit statuses as they are')]
    public function testChecksApiError(): void
    {
        $combined = $this->getCombinedStatus([self::commitStatus('pending')], 'pending', null);

        $this->assertSame('pending', $combined->state);
        $this->assertCount(1, $combined->statuses);
    }
}
