<?php

/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2026 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Helper;

use Joomla\Http\Exception\UnexpectedResponseException;

/**
 * Helper working out which labels and which milestone of an issue have to be changed on GitHub.
 *
 * Only the changes the user made in the tracker are sent to GitHub, so labels or a milestone set on GitHub
 * which the tracker does not know about (yet) are left alone.
 *
 * @since  1.0
 */
class IssueChangesHelper
{
    /**
     * Compute the labels to add to and to remove from an issue.
     *
     * @param   array  $storedIds      The label IDs stored with the issue in the tracker.
     * @param   array  $submittedIds   The label IDs submitted by the user.
     * @param   array  $projectLabels  The project labels, label ID => object with a `name` property.
     *
     * @return  array  An array with the keys `add` and `remove`, each holding a list of label names.
     *
     * @since   1.0
     */
    public static function getLabelChanges(array $storedIds, array $submittedIds, array $projectLabels): array
    {
        $stored    = self::normaliseIds($storedIds);
        $submitted = self::normaliseIds($submittedIds);

        $changes = ['add' => [], 'remove' => []];

        foreach (array_diff($submitted, $stored) as $id) {
            if (isset($projectLabels[$id])) {
                $changes['add'][] = $projectLabels[$id]->name;
            }
        }

        foreach (array_diff($stored, $submitted) as $id) {
            if (isset($projectLabels[$id])) {
                $changes['remove'][] = $projectLabels[$id]->name;
            }
        }

        return $changes;
    }

    /**
     * Compute the milestone change for an issue.
     *
     * @param   mixed  $storedId           The milestone ID stored with the issue in the tracker (empty for none).
     * @param   mixed  $submittedId        The milestone ID submitted by the user (empty for none).
     * @param   array  $projectMilestones  The project milestones, objects with `milestone_id` and `milestone_number`.
     *
     * @return  integer|string|null  The GitHub milestone number to set, an empty string to remove the milestone,
     *                               or null if the milestone must not be changed.
     *
     * @since   1.0
     */
    public static function getMilestoneChange($storedId, $submittedId, array $projectMilestones): int|string|null
    {
        $storedId    = (int) $storedId;
        $submittedId = (int) $submittedId;

        if ($storedId === $submittedId) {
            return null;
        }

        if ($submittedId === 0) {
            return '';
        }

        foreach ($projectMilestones as $milestone) {
            if ((int) $milestone->milestone_id === $submittedId) {
                return (int) $milestone->milestone_number;
            }
        }

        // Unknown milestone, don't touch GitHub.
        return null;
    }

    /**
     * Check whether a milestone change is reflected in the issue data returned by GitHub.
     *
     * GitHub silently drops the milestone when the user has no push access to the repository.
     *
     * @param   integer|string  $milestone  The milestone number which was set, or an empty string if it was removed.
     * @param   object          $issue      The issue data returned by GitHub.
     *
     * @return  boolean
     *
     * @since   1.0
     */
    public static function isMilestoneApplied(int|string $milestone, object $issue): bool
    {
        if ($milestone === '') {
            return empty($issue->milestone);
        }

        return !empty($issue->milestone) && (int) $issue->milestone->number === (int) $milestone;
    }

    /**
     * Add and remove labels on a GitHub issue without touching any other label of the issue.
     *
     * The GitHub objects are tried in order: if one lacks the permission (a 403 or 404 response, or the change is
     * silently dropped), the next one is used. Usually this is the current user first and the edit bot second.
     *
     * @param   array    $gitHubs  The GitHub objects to try, in order.
     * @param   string   $owner    The owner of the GitHub repository.
     * @param   string   $repo     The name of the GitHub repository.
     * @param   integer  $number   The issue number.
     * @param   array    $add      The names of the labels to add.
     * @param   array    $remove   The names of the labels to remove.
     *
     * @return  void
     *
     * @since   1.0
     * @throws  UnexpectedResponseException
     */
    public static function applyLabelChanges(array $gitHubs, string $owner, string $repo, int $number, array $add, array $remove): void
    {
        if ($add) {
            self::tryInOrder(
                $gitHubs,
                static function ($gitHub) use ($owner, $repo, $number, $add): bool {
                    $labels = self::getLabelNames($gitHub->issues->labels->add($owner, $repo, $number, array_values($add)));

                    return !array_diff($add, $labels);
                }
            );
        }

        foreach ($remove as $name) {
            self::tryInOrder(
                $gitHubs,
                static function ($gitHub) use ($owner, $repo, $number, $name): bool {
                    try {
                        $labels = self::getLabelNames($gitHub->issues->labels->removeFromIssue($owner, $repo, $number, $name));
                    } catch (UnexpectedResponseException $exception) {
                        // GitHub answers with a 404 if the label is not set on the issue (anymore).
                        if ($exception->getResponse()->getStatusCode() == 404) {
                            return true;
                        }

                        throw $exception;
                    }

                    return !\in_array($name, $labels, true);
                }
            );
        }
    }

    /**
     * Run a GitHub action with each of the GitHub objects until it succeeds.
     *
     * @param   array     $gitHubs  The GitHub objects to try, in order.
     * @param   callable  $action   The action, returns true when the change has been applied.
     *
     * @return  void
     *
     * @since   1.0
     * @throws  UnexpectedResponseException
     */
    private static function tryInOrder(array $gitHubs, callable $action): void
    {
        $lastException = null;

        foreach ($gitHubs as $gitHub) {
            try {
                if ($action($gitHub)) {
                    return;
                }

                $lastException = null;
            } catch (UnexpectedResponseException $exception) {
                // GitHub will return either a 403 or 404 in case there is a permission problem.
                if (!\in_array($exception->getResponse()->getStatusCode(), [403, 404])) {
                    throw $exception;
                }

                $lastException = $exception;
            }
        }

        if ($lastException) {
            throw $lastException;
        }
    }

    /**
     * Get the label names from a GitHub labels response.
     *
     * @param   mixed  $response  The list of label objects returned by GitHub.
     *
     * @return  string[]
     *
     * @since   1.0
     */
    private static function getLabelNames($response): array
    {
        $names = [];

        foreach ((array) $response as $label) {
            if (isset($label->name)) {
                $names[] = $label->name;
            }
        }

        return $names;
    }

    /**
     * Normalise a list of IDs to unique, non-zero integers.
     *
     * @param   array  $ids  The IDs.
     *
     * @return  integer[]
     *
     * @since   1.0
     */
    private static function normaliseIds(array $ids): array
    {
        return array_values(array_unique(array_filter(array_map('intval', $ids))));
    }
}
