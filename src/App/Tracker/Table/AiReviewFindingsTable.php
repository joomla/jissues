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
 * Table interface class for the #__ai_review_findings table
 *
 * One row is one finding of an AI review, for example improved testing instructions or a possible duplicate.
 *
 * @property   integer  $id              PK
 * @property   integer  $review_id       PK of the review in the ai_reviews table
 * @property   string   $kind            The type of the finding, one of the KIND_* constants
 * @property   string   $rating          Rating for testing instructions and test suitability
 * @property   string   $confidence      low, medium or high
 * @property   string   $rationale       Reasoning and evidence
 * @property   integer  $related_number  Number of the duplicate or fixing item
 * @property   string   $proposal        Proposed text
 * @property   string   $state           One of the STATE_* constants
 * @property   string   $final_text      The text actually sent by the admin
 * @property   string   $processed_by    Username of the admin who processed the finding
 * @property   string   $processed_date  When the finding was processed
 *
 * @since  1.0
 */
class AiReviewFindingsTable extends AbstractDatabaseTable
{
    public const KIND_TESTING_INSTRUCTIONS = 'testing_instructions';
    public const KIND_TEST_SUITABILITY     = 'test_suitability';
    public const KIND_DUPLICATE            = 'duplicate';
    public const KIND_FIXED_ELSEWHERE      = 'fixed_elsewhere';
    public const KIND_OBSOLETE             = 'obsolete';
    public const KIND_ABANDONED            = 'abandoned';
    public const KIND_NEEDS_INFO           = 'needs_info';

    /**
     * All known kinds, in the order they are shown
     */
    public const KINDS = [
        self::KIND_TESTING_INSTRUCTIONS,
        self::KIND_TEST_SUITABILITY,
        self::KIND_DUPLICATE,
        self::KIND_FIXED_ELSEWHERE,
        self::KIND_OBSOLETE,
        self::KIND_ABANDONED,
        self::KIND_NEEDS_INFO,
    ];

    /**
     * Not processed yet
     */
    public const STATE_OPEN = 'open';

    /**
     * The admin adopted the proposal, for example replaced the testing instructions or closed a duplicate
     */
    public const STATE_ACCEPTED = 'accepted';

    /**
     * The admin rejected the finding
     */
    public const STATE_DISMISSED = 'dismissed';

    /**
     * The admin handled the finding by hand
     */
    public const STATE_DONE = 'done';

    /**
     * All known states
     */
    public const STATES = [
        self::STATE_OPEN,
        self::STATE_ACCEPTED,
        self::STATE_DISMISSED,
        self::STATE_DONE,
    ];

    /**
     * Constructor
     *
     * @param   DatabaseInterface  $database  A database connector object.
     *
     * @since   1.0
     */
    public function __construct(DatabaseInterface $database)
    {
        parent::__construct('#__ai_review_findings', 'id', $database);
    }
}
