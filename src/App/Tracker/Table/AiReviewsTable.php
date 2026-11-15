<?php

/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2026 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Table;

use Joomla\Database\DatabaseInterface;
use JTracker\Database\AbstractDatabaseTable;

/**
 * Table interface class for the #__ai_reviews table
 *
 * One row is one AI review of an issue or pull request, imported from a review run.
 *
 * @property   integer  $id                PK
 * @property   integer  $project_id        Project ID
 * @property   integer  $issue_id          PK of the issue in the issues table
 * @property   integer  $issue_number      GitHub issue or pull request number
 * @property   string   $batch             Identifier of the review run
 * @property   string   $reviewed_date     When the review was made
 * @property   string   $head_sha          Reviewed pull request head commit
 * @property   string   $description_hash  SHA1 of the reviewed description
 * @property   string   $source_updated    Last activity of the item at review time
 * @property   string   $model             The model used for the review
 * @property   string   $summary           Short summary of the item
 * @property   string   $state             active or superseded
 *
 * @since  1.0
 */
class AiReviewsTable extends AbstractDatabaseTable
{
    /**
     * The review is the current one for its item
     */
    public const STATE_ACTIVE = 'active';

    /**
     * A newer review of the same item replaced this one
     */
    public const STATE_SUPERSEDED = 'superseded';

    /**
     * Constructor
     *
     * @param   DatabaseInterface  $database  A database connector object.
     *
     * @since   1.0
     */
    public function __construct(DatabaseInterface $database)
    {
        parent::__construct('#__ai_reviews', 'id', $database);
    }

    /**
     * Hash of a description as it is stored in the description_hash column.
     *
     * Line endings are normalised to LF. The same hash is computed in SQL by {@see self::hashDescriptionSql()}, so the
     * definitions must stay identical.
     *
     * @param   string|null  $description  The raw (markdown) description.
     *
     * @return  string
     *
     * @since   1.0
     */
    public static function hashDescription(?string $description): string
    {
        return sha1(str_replace("\r\n", "\n", (string) $description));
    }

    /**
     * SQL expression that computes {@see self::hashDescription()} for a description column.
     *
     * @param   string  $column  The quoted column name.
     *
     * @return  string
     *
     * @since   1.0
     */
    public static function hashDescriptionSql(string $column): string
    {
        return 'SHA1(REPLACE(COALESCE(' . $column . ", ''), CHAR(13, 10), CHAR(10)))";
    }
}
