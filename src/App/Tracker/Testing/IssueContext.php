<?php

/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2026 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Testing;

use App\Tracker\Table\IssuesTable;

/**
 * The data of a pull request needed to record test results.
 *
 * @since  1.0
 */
final class IssueContext
{
    /**
     * Constructor.
     *
     * @param   integer      $id          The issue ID.
     * @param   integer      $number      The pull request number.
     * @param   string       $author      The GitHub login of the author.
     * @param   boolean      $open        Whether the pull request is open.
     * @param   boolean      $hasCode     Whether the item is a pull request.
     * @param   string|null  $headSha     The head commit.
     * @param   string|null  $diffHash    The fingerprint of the diff at the head commit.
     * @param   string[]     $commitShas  The commits of the pull request.
     *
     * @since   1.0
     */
    public function __construct(
        public readonly int $id,
        public readonly int $number,
        public readonly string $author,
        public readonly bool $open,
        public readonly bool $hasCode,
        public ?string $headSha = null,
        public ?string $diffHash = null,
        public readonly array $commitShas = []
    ) {
    }

    /**
     * Create the context from an issue table object.
     *
     * @param   IssuesTable  $table  The issue.
     * @param   boolean      $open   Whether the pull request is open.
     *
     * @return  self
     *
     * @since   1.0
     */
    public static function fromTable(IssuesTable $table, bool $open): self
    {
        $commits = json_decode((string) $table->commits) ?: [];
        $shas    = [];

        foreach ((array) $commits as $commit) {
            if (isset($commit->sha)) {
                $shas[] = (string) $commit->sha;
            }
        }

        return new self(
            (int) $table->id,
            (int) $table->issue_number,
            (string) $table->opened_by,
            $open,
            (bool) $table->has_code,
            $table->pr_head_sha ?: null,
            $table->pr_diff_hash ?: null,
            $shas
        );
    }

    /**
     * Find a commit of the pull request by a (possibly abbreviated) SHA.
     *
     * @param   string  $sha  The SHA.
     *
     * @return  string|null  The full SHA, null if the commit does not belong to the pull request.
     *
     * @since   1.0
     */
    public function findCommit(string $sha): ?string
    {
        $sha = strtolower($sha);

        foreach (array_merge([(string) $this->headSha], $this->commitShas) as $commit) {
            if ($commit !== '' && str_starts_with(strtolower($commit), $sha)) {
                return $commit;
            }
        }

        return null;
    }
}
