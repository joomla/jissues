<?php

/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2026 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Model;

use App\Tracker\Table\AiReviewFindingsTable;
use App\Tracker\Table\AiReviewsTable;
use JTracker\Model\AbstractTrackerDatabaseModel;

/**
 * Model for the promotion of items in the testing channel
 *
 * @since  1.0
 */
class PromotionModel extends AbstractTrackerDatabaseModel
{
    /**
     * Record that an item is being promoted with a label, unless it was promoted with it before.
     *
     * The unique key on (issue_id, label) makes this safe against two webhooks for the same label at the same time.
     *
     * @param   integer  $issueId  The PK of the issue.
     * @param   string   $label    The label.
     * @param   string   $by       GitHub login of the user who set the label.
     *
     * @return  boolean  True if the item may be posted now, false if it was posted before.
     *
     * @since   1.0
     */
    public function claim(int $issueId, string $label, string $by): bool
    {
        $db  = $this->getDb();
        $now = (new \DateTime('now', new \DateTimeZone('UTC')))->format($db->getDateFormat());

        $db->setQuery(
            'INSERT IGNORE INTO ' . $db->quoteName('#__issue_promotions')
            . ' (' . implode(', ', $db->quoteName(['issue_id', 'label', 'posted_date', 'posted_by'])) . ')'
            . ' VALUES (' . implode(', ', [$issueId, $db->quote($label), $db->quote($now), $db->quote($by)]) . ')'
        )->execute();

        return $db->getAffectedRows() === 1;
    }

    /**
     * Remove a claim, so that the item is posted again the next time the label is set. Used when posting failed.
     *
     * @param   integer  $issueId  The PK of the issue.
     * @param   string   $label    The label.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function release(int $issueId, string $label): void
    {
        $db = $this->getDb();

        $db->setQuery(
            $db->getQuery(true)
                ->delete($db->quoteName('#__issue_promotions'))
                ->where($db->quoteName('issue_id') . ' = ' . $issueId)
                ->where($db->quoteName('label') . ' = ' . $db->quote($label))
        )->execute();
    }

    /**
     * Number of successful tests of the current head commit of a pull request.
     *
     * @param   integer      $issueId  The PK of the issue.
     * @param   string|null  $sha      The head commit; null counts the tests of all commits.
     *
     * @return  integer
     *
     * @since   1.0
     */
    public function countSuccessfulTests(int $issueId, ?string $sha): int
    {
        $db    = $this->getDb();
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__issues_tests'))
            ->where($db->quoteName('item_id') . ' = ' . $issueId)
            ->where($db->quoteName('result') . ' = 1');

        if ($sha) {
            $query->where($db->quoteName('sha') . ' = ' . $db->quote($sha));
        }

        return (int) $db->setQuery($query)->loadResult();
    }

    /**
     * The test suitability rating of the current AI review of an item, if there is one.
     *
     * @param   integer  $issueId  The PK of the issue.
     *
     * @return  string|null  yes, partly or no
     *
     * @since   1.0
     */
    public function getTestSuitability(int $issueId): ?string
    {
        $db = $this->getDb();

        $rating = $db->setQuery(
            $db->getQuery(true)
                ->select($db->quoteName('f.rating'))
                ->from($db->quoteName('#__ai_review_findings', 'f'))
                ->join('INNER', $db->quoteName('#__ai_reviews', 'r') . ' ON ' . $db->quoteName('r.id') . ' = ' . $db->quoteName('f.review_id'))
                ->where($db->quoteName('r.issue_id') . ' = ' . $issueId)
                ->where($db->quoteName('r.state') . ' = ' . $db->quote(AiReviewsTable::STATE_ACTIVE))
                ->where($db->quoteName('f.kind') . ' = ' . $db->quote(AiReviewFindingsTable::KIND_TEST_SUITABILITY))
                ->order($db->quoteName('r.reviewed_date') . ' DESC'),
            0,
            1
        )->loadResult();

        return $rating ?: null;
    }
}
