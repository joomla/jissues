<?php

/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2026 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Testing;

/**
 * A test result as stored in the `#__issues_tests` table.
 *
 * The `sha` is the head commit the result counts for. When a pull request is updated without changing its diff
 * (e.g. after a rebase), the result is moved to the new head commit; `testedSha` keeps the commit which was actually tested.
 *
 * @since  1.0
 */
final class TestResult
{
    /**
     * Result value: not tested.
     *
     * @var    integer
     * @since  1.0
     */
    public const NOT_TESTED = 0;

    /**
     * Result value: tested successfully.
     *
     * @var    integer
     * @since  1.0
     */
    public const SUCCESS = 1;

    /**
     * Result value: tested unsuccessfully.
     *
     * @var    integer
     * @since  1.0
     */
    public const FAILURE = 2;

    /**
     * Constructor.
     *
     * @param   integer|null  $id           The primary key, null for new results.
     * @param   integer       $itemId       The issue ID.
     * @param   string        $username     The GitHub login of the tester.
     * @param   integer       $result       The result (one of the class constants).
     * @param   string|null   $sha          The head commit the result counts for.
     * @param   string|null   $testedSha    The commit which has been tested.
     * @param   string|null   $diffHash     The fingerprint of the pull request diff at the time of the test.
     * @param   string|null   $createdDate  The date of the test (database format, UTC).
     * @param   integer|null  $ghCommentId  The GitHub comment containing the test result.
     * @param   string        $comment      The notes of the tester.
     * @param   array         $environment  The test environment.
     * @param   string        $source       Where the result has been submitted (tracker, github, alter, import).
     *
     * @since   1.0
     */
    public function __construct(
        public ?int $id,
        public int $itemId,
        public string $username,
        public int $result,
        public ?string $sha = null,
        public ?string $testedSha = null,
        public ?string $diffHash = null,
        public ?string $createdDate = null,
        public ?int $ghCommentId = null,
        public string $comment = '',
        public array $environment = [],
        public string $source = 'tracker'
    ) {
    }

    /**
     * Create an object from a database row.
     *
     * @param   object  $row  The row of the `#__issues_tests` table.
     *
     * @return  self
     *
     * @since   1.0
     */
    public static function fromRow(object $row): self
    {
        $environment = isset($row->environment) && $row->environment ? json_decode($row->environment, true) : [];

        return new self(
            (int) $row->id,
            (int) $row->item_id,
            (string) $row->username,
            (int) $row->result,
            $row->sha ?? null,
            $row->tested_sha ?? null,
            $row->diff_hash ?? null,
            $row->created_date ?? null,
            isset($row->gh_comment_id) ? (int) $row->gh_comment_id : null,
            (string) ($row->comment ?? ''),
            \is_array($environment) ? $environment : [],
            (string) ($row->source ?? 'tracker')
        );
    }

    /**
     * Get the commit which has been tested.
     *
     * @return  string|null
     *
     * @since   1.0
     */
    public function getTestedSha(): ?string
    {
        return $this->testedSha ?: $this->sha;
    }
}
