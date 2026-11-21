<?php

/**
 * Part of the Joomla! Tracker Package.
 *
 * @copyright  Copyright (C) 2012 - 2026 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace JTracker\Tests\App\Tracker\Testing;

use App\Tracker\Testing\TestResult;
use App\Tracker\Testing\TestSummary;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the summary of test results.
 *
 * @since  1.0
 */
class TestSummaryTest extends TestCase
{
    /**
     * Create a result.
     *
     * @param   integer  $id      The ID.
     * @param   string   $user    The tester.
     * @param   integer  $result  The result.
     * @param   string   $sha     The commit the result counts for.
     *
     * @return  TestResult
     */
    private static function makeResult(int $id, string $user, int $result, string $sha = 'head'): TestResult
    {
        return new TestResult($id, 1, $user, $result, $sha, $sha, null, '2026-11-2' . $id . ' 10:00:00');
    }

    /**
     * Data provider for testStateAndLabel.
     *
     * @return  array
     */
    public static function resultsProvider(): array
    {
        return [
            'no tests'            => [[], 'pending', null],
            'one success'         => [[self::makeResult(1, 'a', TestResult::SUCCESS)], 'pending', TestSummary::LABEL_ONE],
            'two successes'       => [[self::makeResult(1, 'a', TestResult::SUCCESS), self::makeResult(2, 'b', TestResult::SUCCESS)], 'success', TestSummary::LABEL_ENOUGH],
            'two successes, fail' => [
                [self::makeResult(1, 'a', TestResult::SUCCESS), self::makeResult(2, 'b', TestResult::SUCCESS), self::makeResult(3, 'c', TestResult::FAILURE)],
                'pending',
                TestSummary::LABEL_ENOUGH,
            ],
            'two failures'        => [[self::makeResult(1, 'a', TestResult::FAILURE), self::makeResult(2, 'b', TestResult::FAILURE)], 'failure', null],
            'outdated successes'  => [[self::makeResult(1, 'a', TestResult::SUCCESS, 'old'), self::makeResult(2, 'b', TestResult::SUCCESS, 'old')], 'pending', null],
            'not tested ignored'  => [[self::makeResult(1, 'a', TestResult::NOT_TESTED)], 'pending', null],
        ];
    }

    /**
     * Test the state of the commit status and the label.
     *
     * @param   TestResult[]  $results  The results.
     * @param   string        $state    The expected state.
     * @param   string|null   $label    The expected label.
     *
     * @return  void
     */
    #[DataProvider('resultsProvider')]
    public function testStateAndLabel(array $results, string $state, ?string $label): void
    {
        $summary = new TestSummary($results, 'head');

        $this->assertSame($state, $summary->getState());
        $this->assertSame($label, $summary->getLabel());
    }

    /**
     * An outdated result is only listed for users without a current result.
     *
     * @return  void
     */
    public function testOutdatedResults(): void
    {
        $summary = new TestSummary(
            [
                self::makeResult(1, 'a', TestResult::FAILURE, 'old'),
                self::makeResult(2, 'a', TestResult::SUCCESS),
                self::makeResult(3, 'b', TestResult::SUCCESS, 'old'),
            ],
            'head'
        );

        $this->assertSame(['a'], array_keys($summary->current));
        $this->assertSame(['b'], array_keys($summary->outdated));
        $this->assertSame(['a'], $summary->getSuccessful());
        $this->assertSame([], $summary->getFailed());
    }
}
