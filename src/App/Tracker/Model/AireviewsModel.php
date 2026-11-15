<?php

/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2026 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Model;

use App\Tracker\Table\AiReviewFindingsTable;
use Joomla\Database\QueryInterface;
use JTracker\Model\AbstractTrackerListModel;

/**
 * Model for the list of AI review findings
 *
 * @since  1.0
 */
class AireviewsModel extends AbstractTrackerListModel
{
    /**
     * Context string for the model type
     *
     * @var    string
     * @since  1.0
     */
    protected $context = 'tracker.aireviews';

    /**
     * Method to get a QueryInterface object for retrieving the data set from a database.
     *
     * @return  QueryInterface  A QueryInterface object to retrieve the data set.
     *
     * @since   1.0
     */
    protected function getListQuery()
    {
        $db    = $this->getDb();
        $query = $db->getQuery(true);

        $query->select('f.*')
            ->select(
                $db->quoteName(
                    ['r.issue_id', 'r.issue_number', 'r.batch', 'r.reviewed_date', 'r.summary', 'i.title', 'i.has_code', 'i.labels'],
                    ['issue_id', 'issue_number', 'batch', 'reviewed_date', 'summary', 'title', 'has_code', 'labels']
                )
            )
            ->from($db->quoteName('#__ai_review_findings', 'f'))
            ->join('INNER', $db->quoteName('#__ai_reviews', 'r') . ' ON ' . $db->quoteName('r.id') . ' = ' . $db->quoteName('f.review_id'))
            ->join('INNER', $db->quoteName('#__issues', 'i') . ' ON ' . $db->quoteName('i.id') . ' = ' . $db->quoteName('r.issue_id'))
            ->join('INNER', $db->quoteName('#__status', 's') . ' ON ' . $db->quoteName('s.id') . ' = ' . $db->quoteName('i.status'))
            ->where($db->quoteName('r.project_id') . ' = ' . (int) $this->getProject()->project_id);

        AiReviewFindingModel::addSuggestionConditions($db, $query);

        $state = (string) $this->state->get('filter.state', AiReviewFindingsTable::STATE_OPEN);

        if (\in_array($state, AiReviewFindingsTable::STATES, true)) {
            $query->where($db->quoteName('f.state') . ' = ' . $db->quote($state));
        }

        // Open findings are only shown while the review is still valid; processed ones stay visible as a record.
        if ($state === AiReviewFindingsTable::STATE_OPEN) {
            AiReviewFindingModel::addFreshConditions($db, $query);
        }

        $kind = (string) $this->state->get('filter.kind', '');

        if (\in_array($kind, AiReviewFindingsTable::KINDS, true)) {
            $query->where($db->quoteName('f.kind') . ' = ' . $db->quote($kind));
        }

        $confidence = (string) $this->state->get('filter.confidence', '');

        if (\in_array($confidence, ['low', 'medium', 'high'], true)) {
            $query->where($db->quoteName('f.confidence') . ' = ' . $db->quote($confidence));
        }

        $rating = (string) $this->state->get('filter.rating', '');

        if ($rating !== '') {
            $query->where($db->quoteName('f.rating') . ' = ' . $db->quote($rating));
        }

        $type = (string) $this->state->get('filter.type', '');

        if ($type === 'issue' || $type === 'pr') {
            $query->where($db->quoteName('i.has_code') . ' = ' . ($type === 'pr' ? 1 : 0));
        }

        $search = trim((string) $this->state->get('filter.search', ''));

        if ($search !== '') {
            if (ctype_digit(ltrim($search, '#'))) {
                $query->where($db->quoteName('r.issue_number') . ' = ' . (int) ltrim($search, '#'));
            } else {
                $like = $db->quote('%' . $db->escape($search, true) . '%');
                $query->where('(' . $db->quoteName('i.title') . ' LIKE ' . $like . ' OR ' . $db->quoteName('f.rationale') . ' LIKE ' . $like . ')');
            }
        }

        $query->order("FIELD(" . $db->quoteName('f.confidence') . ", 'high', 'medium', 'low')")
            ->order($db->quoteName('r.issue_number') . ' DESC')
            ->order($db->quoteName('f.id'));

        return $query;
    }

    /**
     * Method to get a store id based on the model configuration state.
     *
     * @param   string  $id  An identifier string to generate the store id.
     *
     * @return  string  A store id.
     *
     * @since   1.0
     */
    protected function getStoreId($id = '')
    {
        foreach (['state', 'kind', 'confidence', 'rating', 'type', 'search'] as $filter) {
            $id .= ':' . $this->state->get('filter.' . $filter);
        }

        return parent::getStoreId($id);
    }
}
