<?php

/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2026 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Testing;

/**
 * A `/tested` command found in a GitHub comment.
 *
 * @since  1.0
 */
final class TestCommand
{
    /**
     * The tester reports a successful test.
     *
     * @var    string
     * @since  1.0
     */
    public const SUCCESS = 'success';

    /**
     * The tester reports a failed test.
     *
     * @var    string
     * @since  1.0
     */
    public const FAILURE = 'failure';

    /**
     * A maintainer resets the test results of one user or of all users.
     *
     * @var    string
     * @since  1.0
     */
    public const RESET = 'reset';

    /**
     * The command could not be understood.
     *
     * @var    string
     * @since  1.0
     */
    public const INVALID = 'invalid';

    /**
     * Constructor.
     *
     * @param   string       $action       One of the class constants.
     * @param   string       $notes        The notes of the tester.
     * @param   string|null  $targetUser   The user whose test result should be reset (reset command only).
     * @param   array        $environment  The test environment reported by the tester (e.g. by the Patch Tester).
     *
     * @since   1.0
     */
    public function __construct(
        public readonly string $action,
        public readonly string $notes = '',
        public readonly ?string $targetUser = null,
        public readonly array $environment = []
    ) {
    }

    /**
     * Get the test result value as stored in the database.
     *
     * @return  integer|null  Null for commands which are no test result.
     *
     * @since   1.0
     */
    public function getResult(): ?int
    {
        return match ($this->action) {
            self::SUCCESS => TestResult::SUCCESS,
            self::FAILURE => TestResult::FAILURE,
            default       => null,
        };
    }

    /**
     * Get the commit SHA the tester reported in the environment block, if any.
     *
     * @return  string|null
     *
     * @since   1.0
     */
    public function getReportedSha(): ?string
    {
        $sha = $this->environment['sha'] ?? null;

        return \is_string($sha) && preg_match('/^[0-9a-f]{7,40}$/i', $sha) ? strtolower($sha) : null;
    }
}
