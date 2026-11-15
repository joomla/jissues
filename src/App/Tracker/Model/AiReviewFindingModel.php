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
use Joomla\Database\DatabaseInterface;
use Joomla\Database\QueryInterface;
use JTracker\Model\AbstractTrackerDatabaseModel;

/**
 * Model for single findings of imported AI reviews
 *
 * @since  1.0
 */
class AiReviewFindingModel extends AbstractTrackerDatabaseModel
{
    /**
     * Activity events that show the item changed after the review
     */
    public const STALE_EVENTS = ['comment', 'test_item', 'alter_testresult', 'reopen'];

    /**
     * Add the conditions under which a review is still shown.
     *
     * The review must be active, the item open, and the item must not have changed since the review: same pull request
     * head commit, same description, and no comment, test or reopen after the review. Expects the aliases `r` for
     * #__ai_reviews, `i` for #__issues and `s` for #__status.
     *
     * @param   DatabaseInterface  $db     The database driver.
     * @param   QueryInterface     $query  The query to add the conditions to.
     *
     * @return  QueryInterface
     *
     * @since   1.0
     */
    public static function addFreshConditions(DatabaseInterface $db, QueryInterface $query): QueryInterface
    {
        $events = implode(',', array_map([$db, 'quote'], self::STALE_EVENTS));

        $activities = $db->getQuery(true)
            ->select('1')
            ->from($db->quoteName('#__activities', 'a'))
            ->where($db->quoteName('a.project_id') . ' = ' . $db->quoteName('i.project_id'))
            ->where($db->quoteName('a.issue_number') . ' = ' . $db->quoteName('i.issue_number'))
            ->where($db->quoteName('a.event') . ' IN (' . $events . ')')
            ->where($db->quoteName('a.created_date') . ' > ' . $db->quoteName('r.source_updated'));

        return $query
            ->where($db->quoteName('r.state') . ' = ' . $db->quote(AiReviewsTable::STATE_ACTIVE))
            ->where($db->quoteName('s.closed') . ' = 0')
            ->where(
                '(' . $db->quoteName('r.head_sha') . ' IS NULL OR ' . $db->quoteName('i.pr_head_sha') . ' = ' . $db->quoteName('r.head_sha') . ')'
            )
            ->where($db->quoteName('r.description_hash') . ' = ' . AiReviewsTable::hashDescriptionSql($db->quoteName('i.description_raw')))
            ->where('NOT EXISTS (' . $activities . ')');
    }

    /**
     * Add the conditions that leave out findings without a suggestion for the admin.
     *
     * A testing instructions finding only suggests something when it contains proposed instructions. Without one the
     * review rated the existing instructions as good or not applicable, and there is nothing to process. Expects the
     * alias `f` for #__ai_review_findings.
     *
     * @param   DatabaseInterface  $db     The database driver.
     * @param   QueryInterface     $query  The query to add the conditions to.
     *
     * @return  QueryInterface
     *
     * @since   1.0
     */
    public static function addSuggestionConditions(DatabaseInterface $db, QueryInterface $query): QueryInterface
    {
        return $query->where(
            '(' . $db->quoteName('f.kind') . ' <> ' . $db->quote(AiReviewFindingsTable::KIND_TESTING_INSTRUCTIONS)
            . ' OR COALESCE(' . $db->quoteName('f.proposal') . ", '') <> '')"
        );
    }

    /**
     * Get the open findings of the current review of an item, if the review is still valid.
     *
     * @param   integer  $issueId  The PK of the issue.
     *
     * @return  object[]
     *
     * @since   1.0
     */
    public function getOpenFindingsForIssue(int $issueId): array
    {
        $db    = $this->getDb();
        $query = $this->getFindingQuery()
            ->where($db->quoteName('r.project_id') . ' = ' . (int) $this->getProject()->project_id)
            ->where($db->quoteName('r.issue_id') . ' = ' . $issueId)
            ->where($db->quoteName('f.state') . ' = ' . $db->quote(AiReviewFindingsTable::STATE_OPEN))
            ->order($db->quoteName('f.id'));

        self::addFreshConditions($db, $query);
        self::addSuggestionConditions($db, $query);

        return $db->setQuery($query)->loadObjectList();
    }

    /**
     * Get an open finding of the current project that can still be processed.
     *
     * @param   integer  $findingId  The PK of the finding.
     *
     * @return  object
     *
     * @since   1.0
     * @throws  \RuntimeException if the finding doesn't exist, was processed already, or the item changed since the review.
     */
    public function getOpenFinding(int $findingId): object
    {
        $db    = $this->getDb();
        $query = $this->getFindingQuery()
            ->where($db->quoteName('f.id') . ' = ' . $findingId)
            ->where($db->quoteName('r.project_id') . ' = ' . (int) $this->getProject()->project_id);

        $finding = $db->setQuery($query)->loadObject();

        if (!$finding) {
            throw new \RuntimeException('The finding does not exist.');
        }

        if ($finding->state !== AiReviewFindingsTable::STATE_OPEN) {
            throw new \RuntimeException('The finding was already processed.');
        }

        $query = $this->getFindingQuery()->where($db->quoteName('f.id') . ' = ' . $findingId);
        self::addFreshConditions($db, $query);

        if (!$db->setQuery($query)->loadObject()) {
            throw new \RuntimeException('The item changed or was closed since the review. It will be reviewed again in the next review run.');
        }

        return $finding;
    }

    /**
     * Record that an admin processed a finding.
     *
     * Only an open finding is changed, so two admins processing the same finding at the same time can't both succeed.
     *
     * @param   integer      $findingId  The PK of the finding.
     * @param   string       $state      The new state, one of the AiReviewFindingsTable::STATE_* constants except open.
     * @param   string       $username   The admin's username.
     * @param   string|null  $finalText  The text that was actually sent, if any.
     *
     * @return  void
     *
     * @since   1.0
     * @throws  \InvalidArgumentException if the state is not a processed state.
     * @throws  \RuntimeException if the finding was not open any more.
     */
    public function process(int $findingId, string $state, string $username, ?string $finalText = null): void
    {
        if ($state === AiReviewFindingsTable::STATE_OPEN || !\in_array($state, AiReviewFindingsTable::STATES, true)) {
            throw new \InvalidArgumentException('Invalid finding state ' . $state);
        }

        $db    = $this->getDb();
        $query = $db->getQuery(true)
            ->update($db->quoteName('#__ai_review_findings'))
            ->set($db->quoteName('state') . ' = ' . $db->quote($state))
            ->set($db->quoteName('processed_by') . ' = ' . $db->quote($username))
            ->set($db->quoteName('processed_date') . ' = ' . $db->quote((new \DateTime('now', new \DateTimeZone('UTC')))->format($db->getDateFormat())))
            ->where($db->quoteName('id') . ' = ' . $findingId)
            ->where($db->quoteName('state') . ' = ' . $db->quote(AiReviewFindingsTable::STATE_OPEN));

        if ($finalText !== null) {
            $query->set($db->quoteName('final_text') . ' = ' . $db->quote($finalText));
        }

        $db->setQuery($query)->execute();

        if ($db->getAffectedRows() < 1) {
            throw new \RuntimeException('The finding was already processed.');
        }
    }

    /**
     * Store a description an admin changed through a finding, and keep the review valid for it.
     *
     * The webhook for the edit stores the same description later. Updating the review's description hash keeps the
     * other findings of the review visible, because the change was made from the review itself.
     *
     * @param   object       $finding  The finding, as returned by getOpenFinding().
     * @param   string       $raw      The new raw (markdown) description, as GitHub returned it.
     * @param   string|null  $html     The rendered description, or null to keep the stored one.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function storeChangedDescription(object $finding, string $raw, ?string $html): void
    {
        $db = $this->getDb();

        $query = $db->getQuery(true)
            ->update($db->quoteName('#__issues'))
            ->set($db->quoteName('description_raw') . ' = ' . $db->quote($raw))
            ->where($db->quoteName('id') . ' = ' . (int) $finding->issue_id);

        if ($html !== null) {
            $query->set($db->quoteName('description') . ' = ' . $db->quote($html));
        }

        $db->setQuery($query)->execute();

        $db->setQuery(
            $db->getQuery(true)
                ->update($db->quoteName('#__ai_reviews'))
                ->set($db->quoteName('description_hash') . ' = ' . $db->quote(AiReviewsTable::hashDescription($raw)))
                ->where($db->quoteName('id') . ' = ' . (int) $finding->review_id)
        )->execute();
    }

    /**
     * Base query for a finding with its review and item.
     *
     * @return  QueryInterface
     *
     * @since   1.0
     */
    private function getFindingQuery(): QueryInterface
    {
        $db = $this->getDb();

        return $db->getQuery(true)
            ->select('f.*')
            ->select(
                $db->quoteName(
                    ['r.issue_id', 'r.issue_number', 'r.batch', 'r.reviewed_date', 'r.head_sha', 'r.description_hash', 'r.summary', 'r.model'],
                    ['issue_id', 'issue_number', 'batch', 'reviewed_date', 'head_sha', 'description_hash', 'summary', 'model']
                )
            )
            ->select($db->quoteName(['i.title', 'i.has_code', 'i.description_raw'], ['title', 'has_code', 'description_raw']))
            ->from($db->quoteName('#__ai_review_findings', 'f'))
            ->join('INNER', $db->quoteName('#__ai_reviews', 'r') . ' ON ' . $db->quoteName('r.id') . ' = ' . $db->quoteName('f.review_id'))
            ->join('INNER', $db->quoteName('#__issues', 'i') . ' ON ' . $db->quoteName('i.id') . ' = ' . $db->quoteName('r.issue_id'))
            ->join('INNER', $db->quoteName('#__status', 's') . ' ON ' . $db->quoteName('s.id') . ' = ' . $db->quoteName('i.status'));
    }
}
