<?php

/**
 * Part of the Joomla Framework Github Package
 *
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace JTracker\Github\Package\Repositories;

use JTracker\Github\Package;

/**
 * GitHub API References class for the Joomla Framework.
 *
 * @documentation http://developer.github.com/v3/repos/statuses
 *
 * @since  1.0
 */
class Statuses extends Package
{
    /**
     * Method to create a status.
     *
     * @param   string  $user         The name of the owner of the GitHub repository.
     * @param   string  $repo         The name of the GitHub repository.
     * @param   string  $sha          The SHA1 value for which to set the status.
     * @param   string  $state        The state (pending, success, error or failure).
     * @param   string  $targetUrl    Optional target URL.
     * @param   string  $description  Optional description for the status.
     * @param   string  $context      A string label to differentiate this status from the status of other systems.
     *                                Default: "default"
     *
     * @throws \InvalidArgumentException
     * @throws \DomainException
     *
     * @return object
     *
     * @since   1.0
     */
    public function create($user, $repo, $sha, $state, $targetUrl = null, $description = null, $context = null)
    {
        // Build the request path.
        $path = '/repos/' . $user . '/' . $repo . '/statuses/' . $sha;

        if (!\in_array($state, ['pending', 'success', 'error', 'failure'])) {
            throw new \InvalidArgumentException('State must be one of pending, success, error or failure.');
        }

        // Build the request data.
        $data = [
            'state' => $state,
        ];

        if ($targetUrl !== null) {
            $data['target_url'] = $targetUrl;
        }

        if ($description !== null) {
            $data['description'] = $description;
        }

        if ($context !== null) {
            $data['context'] = $context;
        }

        // Send the request.
        return $this->processResponse(
            $this->client->post($this->fetchUrl($path), json_encode($data)),
            201
        );
    }

    /**
     * Method to list statuses for an SHA.
     *
     * @param   string  $user  The name of the owner of the GitHub repository.
     * @param   string  $repo  The name of the GitHub repository.
     * @param   string  $sha   SHA1 for which to get the statuses.
     *
     * @return  array
     *
     * @since   1.0
     */
    public function getList($user, $repo, $sha)
    {
        // Build the request path.
        $path = '/repos/' . $user . '/' . $repo . '/statuses/' . $sha;

        // Send the request.
        return $this->processResponse($this->client->get($this->fetchUrl($path)));
    }

    /**
     * Method to access a combined view of commit statuses for a given ref.
     *
     * @param   string  $user  The name of the owner of the GitHub repository.
     * @param   string  $repo  The name of the GitHub repository.
     * @param   string  $ref   Ref to fetch the status for. It can be a SHA, a branch name, or a tag name.
     *
     * @return  \stdClass  The decoded JSON response
     *
     * @since   1.0
     */
    public function getCombined($user, $repo, $ref)
    {
        // Build the request path.
        $path = '/repos/' . $user . '/' . $repo . '/commits/' . $ref . '/status';

        // Send the request.
        return $this->processResponse($this->client->get($this->fetchUrl($path)));
    }

    /**
     * Method to list the check runs for a given ref.
     *
     * Check runs are how GitHub Actions and other GitHub Apps report CI results. They are not part of the combined
     * commit status.
     *
     * @param   string  $user  The name of the owner of the GitHub repository.
     * @param   string  $repo  The name of the GitHub repository.
     * @param   string  $ref   Ref to fetch the check runs for. It can be a SHA, a branch name, or a tag name.
     *
     * @return  object  An object with the properties total_count and check_runs.
     *
     * @documentation  https://docs.github.com/en/rest/checks/runs#list-check-runs-for-a-git-reference
     * @since   1.0
     */
    public function getCheckRuns($user, $repo, $ref)
    {
        // Build the request path.
        $path = '/repos/' . $user . '/' . $repo . '/commits/' . $ref . '/check-runs';

        // Send the request, with the largest page GitHub allows.
        return $this->processResponse($this->client->get($this->fetchUrl($path, 0, 100)));
    }
}
