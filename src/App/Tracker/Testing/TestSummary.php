<?php

/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2026 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Testing;

/**
 * Summary of the test results of a pull request.
 *
 * Results count for the current head commit only. Older results are listed as outdated; a result is moved to a new
 * head commit when the diff of the pull request did not change (see TestResultService::carryOverResults()).
 *
 * @since  1.0
 */
final class TestSummary
{
    /**
     * Number of successful tests needed.
     *
     * @var    integer
     * @since  1.0
     */
    public const REQUIRED_SUCCESSES = 2;

    /**
     * Label for a pull request with one successful test.
     *
     * @var    string
     * @since  1.0
     */
    public const LABEL_ONE = 'Tested: 1';

    /**
     * Label for a pull request with the required number of successful tests.
     *
     * @var    string
     * @since  1.0
     */
    public const LABEL_ENOUGH = 'Tested: 2+';

    /**
     * Results counting for the head commit, keyed by user name.
     *
     * @var    TestResult[]
     * @since  1.0
     */
    public readonly array $current;

    /**
     * The latest outdated result of users without a current result, keyed by user name.
     *
     * @var    TestResult[]
     * @since  1.0
     */
    public readonly array $outdated;

    /**
     * Constructor.
     *
     * @param   TestResult[]  $results  All test results of the pull request.
     * @param   string|null   $headSha  The head commit of the pull request.
     *
     * @since   1.0
     */
    public function __construct(array $results, ?string $headSha)
    {
        $current  = [];
        $outdated = [];

        foreach ($results as $result) {
            if (!\in_array($result->result, [TestResult::SUCCESS, TestResult::FAILURE], true)) {
                continue;
            }

            $key = strtolower($result->username);

            if ($headSha !== null && $result->sha === $headSha) {
                if (!isset($current[$key]) || $this->isNewer($result, $current[$key])) {
                    $current[$key] = $result;
                }

                continue;
            }

            if (!isset($outdated[$key]) || $this->isNewer($result, $outdated[$key])) {
                $outdated[$key] = $result;
            }
        }

        $this->current  = $this->sort($current);
        $this->outdated = $this->sort(array_diff_key($outdated, $current));
    }

    /**
     * Get the user names with a successful test for the head commit.
     *
     * @return  string[]
     *
     * @since   1.0
     */
    public function getSuccessful(): array
    {
        return $this->getUsers(TestResult::SUCCESS);
    }

    /**
     * Get the user names with a failed test for the head commit.
     *
     * @return  string[]
     *
     * @since   1.0
     */
    public function getFailed(): array
    {
        return $this->getUsers(TestResult::FAILURE);
    }

    /**
     * Get the state of the `JTracker/HumanTestResults` commit status.
     *
     * The rule is the one the tracker always used: success with 2 or more successful tests and no failure,
     * failure with 2 or more failed tests and no success, pending otherwise.
     *
     * @return  string  One of success, failure or pending.
     *
     * @since   1.0
     */
    public function getState(): string
    {
        $successes = \count($this->getSuccessful());
        $failures  = \count($this->getFailed());

        if ($successes >= self::REQUIRED_SUCCESSES && $failures === 0) {
            return 'success';
        }

        if ($successes === 0 && $failures >= self::REQUIRED_SUCCESSES) {
            return 'failure';
        }

        return 'pending';
    }

    /**
     * Get the label reflecting the number of successful tests.
     *
     * @return  string|null
     *
     * @since   1.0
     */
    public function getLabel(): ?string
    {
        $successes = \count($this->getSuccessful());

        if ($successes >= self::REQUIRED_SUCCESSES) {
            return self::LABEL_ENOUGH;
        }

        return $successes === 1 ? self::LABEL_ONE : null;
    }

    /**
     * Get the user names with the given result for the head commit.
     *
     * @param   integer  $result  The result value.
     *
     * @return  string[]
     *
     * @since   1.0
     */
    private function getUsers(int $result): array
    {
        $users = [];

        foreach ($this->current as $test) {
            if ($test->result === $result) {
                $users[] = $test->username;
            }
        }

        return $users;
    }

    /**
     * Check if a result is newer than another one.
     *
     * @param   TestResult  $result  The result to check.
     * @param   TestResult  $other   The result to compare with.
     *
     * @return  boolean
     *
     * @since   1.0
     */
    private function isNewer(TestResult $result, TestResult $other): bool
    {
        return [(string) $result->createdDate, (int) $result->id] > [(string) $other->createdDate, (int) $other->id];
    }

    /**
     * Sort results by date.
     *
     * @param   TestResult[]  $results  The results.
     *
     * @return  TestResult[]
     *
     * @since   1.0
     */
    private function sort(array $results): array
    {
        uasort(
            $results,
            static fn (TestResult $a, TestResult $b) => [(string) $a->createdDate, (int) $a->id] <=> [(string) $b->createdDate, (int) $b->id]
        );

        return $results;
    }
}
