<?php

/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2026 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Testing;

use App\Tracker\Model\ActivityModel;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/**
 * Database access for test results.
 *
 * @since  1.0
 */
class TestResultRepository
{
    /**
     * Constructor.
     *
     * @param   DatabaseInterface  $db  The database driver.
     *
     * @since   1.0
     */
    public function __construct(private readonly DatabaseInterface $db)
    {
    }

    /**
     * Get all test results of an issue.
     *
     * @param   integer  $itemId  The issue ID.
     *
     * @return  TestResult[]
     *
     * @since   1.0
     */
    public function getResults(int $itemId): array
    {
        $query = $this->db->getQuery(true)
            ->select('*')
            ->from($this->db->quoteName('#__issues_tests'))
            ->where($this->db->quoteName('item_id') . ' = :itemId')
            ->bind(':itemId', $itemId, ParameterType::INTEGER)
            ->order($this->db->quoteName('id'));

        return array_map([TestResult::class, 'fromRow'], $this->db->setQuery($query)->loadObjectList());
    }

    /**
     * Find the test result recorded from a GitHub comment.
     *
     * @param   integer  $commentId  The GitHub comment ID.
     *
     * @return  TestResult|null
     *
     * @since   1.0
     */
    public function findByCommentId(int $commentId): ?TestResult
    {
        $query = $this->db->getQuery(true)
            ->select('*')
            ->from($this->db->quoteName('#__issues_tests'))
            ->where($this->db->quoteName('gh_comment_id') . ' = :commentId')
            ->bind(':commentId', $commentId, ParameterType::INTEGER);

        $row = $this->db->setQuery($query)->loadObject();

        return $row ? TestResult::fromRow($row) : null;
    }

    /**
     * Find the test result of a user for a commit.
     *
     * @param   integer  $itemId    The issue ID.
     * @param   string   $username  The user name.
     * @param   string   $sha       The commit SHA the result counts for.
     *
     * @return  TestResult|null
     *
     * @since   1.0
     */
    public function findByUser(int $itemId, string $username, string $sha): ?TestResult
    {
        $query = $this->db->getQuery(true)
            ->select('*')
            ->from($this->db->quoteName('#__issues_tests'))
            ->where($this->db->quoteName('item_id') . ' = :itemId')
            ->where($this->db->quoteName('username') . ' = :username')
            ->where($this->db->quoteName('sha') . ' = :sha')
            ->bind(':itemId', $itemId, ParameterType::INTEGER)
            ->bind(':username', $username)
            ->bind(':sha', $sha)
            ->order($this->db->quoteName('id') . ' DESC');

        $row = $this->db->setQuery($query, 0, 1)->loadObject();

        return $row ? TestResult::fromRow($row) : null;
    }

    /**
     * Store a test result.
     *
     * @param   TestResult  $result  The test result. The ID is set for new results.
     *
     * @return  TestResult
     *
     * @since   1.0
     */
    public function save(TestResult $result): TestResult
    {
        $data = (object) [
            'item_id'       => $result->itemId,
            'username'      => $result->username,
            'result'        => $result->result,
            'sha'           => $result->sha,
            'tested_sha'    => $result->testedSha,
            'diff_hash'     => $result->diffHash,
            'created_date'  => $result->createdDate,
            'gh_comment_id' => $result->ghCommentId,
            'comment'       => $result->comment,
            'environment'   => $result->environment ? json_encode($result->environment) : null,
            'source'        => $result->source,
        ];

        if ($result->id) {
            $data->id = $result->id;

            $this->db->updateObject('#__issues_tests', $data, 'id', true);

            return $result;
        }

        $this->db->insertObject('#__issues_tests', $data, 'id');

        $result->id = (int) $data->id;

        return $result;
    }

    /**
     * Delete a test result.
     *
     * @param   integer  $id  The ID of the test result.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function delete(int $id): void
    {
        $query = $this->db->getQuery(true)
            ->delete($this->db->quoteName('#__issues_tests'))
            ->where($this->db->quoteName('id') . ' = :id')
            ->bind(':id', $id, ParameterType::INTEGER);

        $this->db->setQuery($query)->execute();
    }

    /**
     * Delete the test results of an issue.
     *
     * @param   integer      $itemId    The issue ID.
     * @param   string|null  $username  Only delete the results of this user.
     *
     * @return  integer  The number of deleted results.
     *
     * @since   1.0
     */
    public function deleteForItem(int $itemId, ?string $username = null): int
    {
        $query = $this->db->getQuery(true)
            ->delete($this->db->quoteName('#__issues_tests'))
            ->where($this->db->quoteName('item_id') . ' = :itemId')
            ->bind(':itemId', $itemId, ParameterType::INTEGER);

        if ($username !== null) {
            $query->where($this->db->quoteName('username') . ' = :username')
                ->bind(':username', $username);
        }

        $this->db->setQuery($query)->execute();

        return (int) $this->db->getAffectedRows();
    }

    /**
     * Move the test results with an unchanged diff to a new head commit.
     *
     * @param   integer  $itemId    The issue ID.
     * @param   string   $diffHash  The fingerprint of the current diff.
     * @param   string   $headSha   The new head commit.
     *
     * @return  integer  The number of moved results.
     *
     * @since   1.0
     */
    public function carryOver(int $itemId, string $diffHash, string $headSha): int
    {
        // Results without a tested SHA keep the commit they have been recorded for
        $this->db->setQuery(
            $this->db->getQuery(true)
                ->update($this->db->quoteName('#__issues_tests'))
                ->set($this->db->quoteName('tested_sha') . ' = ' . $this->db->quoteName('sha'))
                ->where($this->db->quoteName('item_id') . ' = :itemId')
                ->where($this->db->quoteName('diff_hash') . ' = :diffHash')
                ->where($this->db->quoteName('tested_sha') . ' IS NULL')
                ->bind(':itemId', $itemId, ParameterType::INTEGER)
                ->bind(':diffHash', $diffHash)
        )->execute();

        $this->db->setQuery(
            $this->db->getQuery(true)
                ->update($this->db->quoteName('#__issues_tests'))
                ->set($this->db->quoteName('sha') . ' = :headSha')
                ->where($this->db->quoteName('item_id') . ' = :itemId')
                ->where($this->db->quoteName('diff_hash') . ' = :diffHash')
                ->where($this->db->quoteName('sha') . ' <> :headSha2')
                ->bind(':headSha', $headSha)
                ->bind(':headSha2', $headSha)
                ->bind(':itemId', $itemId, ParameterType::INTEGER)
                ->bind(':diffHash', $diffHash)
        )->execute();

        return (int) $this->db->getAffectedRows();
    }

    /**
     * Set the diff fingerprint of the tests recorded for the head commit, which have none yet.
     *
     * Used for results recorded before the project switched to testing on GitHub.
     *
     * @param   integer  $itemId    The issue ID.
     * @param   string   $headSha   The head commit.
     * @param   string   $diffHash  The fingerprint of the diff at the head commit.
     *
     * @return  integer  The number of updated results.
     *
     * @since   1.0
     */
    public function backfillDiffHash(int $itemId, string $headSha, string $diffHash): int
    {
        $this->db->setQuery(
            $this->db->getQuery(true)
                ->update($this->db->quoteName('#__issues_tests'))
                ->set($this->db->quoteName('diff_hash') . ' = :diffHash')
                ->where($this->db->quoteName('item_id') . ' = :itemId')
                ->where($this->db->quoteName('sha') . ' = :headSha')
                ->where($this->db->quoteName('diff_hash') . ' IS NULL')
                ->bind(':diffHash', $diffHash)
                ->bind(':itemId', $itemId, ParameterType::INTEGER)
                ->bind(':headSha', $headSha)
        )->execute();

        return (int) $this->db->getAffectedRows();
    }

    /**
     * Store the diff fingerprint of an issue.
     *
     * @param   integer  $itemId    The issue ID.
     * @param   string   $diffHash  The fingerprint.
     * @param   string   $headSha   The head commit the fingerprint belongs to.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function setIssueDiffHash(int $itemId, string $diffHash, string $headSha): void
    {
        $this->db->setQuery(
            $this->db->getQuery(true)
                ->update($this->db->quoteName('#__issues'))
                ->set($this->db->quoteName('pr_diff_hash') . ' = :diffHash')
                ->set($this->db->quoteName('pr_head_sha') . ' = :headSha')
                ->where($this->db->quoteName('id') . ' = :itemId')
                ->bind(':diffHash', $diffHash)
                ->bind(':headSha', $headSha)
                ->bind(':itemId', $itemId, ParameterType::INTEGER)
        )->execute();
    }

    /**
     * Set the status of an issue.
     *
     * @param   integer       $itemId        The issue ID.
     * @param   integer       $status        The new status ID.
     * @param   integer|null  $onlyIfStatus  Only change the status if the issue currently has this status.
     *
     * @return  boolean  True if the status has been changed.
     *
     * @since   1.0
     */
    public function setIssueStatus(int $itemId, int $status, ?int $onlyIfStatus = null): bool
    {
        $query = $this->db->getQuery(true)
            ->update($this->db->quoteName('#__issues'))
            ->set($this->db->quoteName('status') . ' = :status')
            ->where($this->db->quoteName('id') . ' = :itemId')
            ->bind(':status', $status, ParameterType::INTEGER)
            ->bind(':itemId', $itemId, ParameterType::INTEGER);

        if ($onlyIfStatus !== null) {
            $query->where($this->db->quoteName('status') . ' = :onlyIfStatus')
                ->bind(':onlyIfStatus', $onlyIfStatus, ParameterType::INTEGER);
        }

        $this->db->setQuery($query)->execute();

        return $this->db->getAffectedRows() > 0;
    }

    /**
     * Add an activity event.
     *
     * @param   string       $event        The event name (e.g. test_item).
     * @param   string       $userName     The user name.
     * @param   integer      $projectId    The project ID.
     * @param   integer      $issueNumber  The issue number.
     * @param   string       $text         The event text.
     * @param   string|null  $dateTime     The date of the event.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function addActivity(string $event, string $userName, int $projectId, int $issueNumber, string $text, ?string $dateTime = null): void
    {
        (new ActivityModel($this->db))->addActivityEvent($event, $dateTime ?? 'now', $userName, $projectId, $issueNumber, null, $text);
    }
}
