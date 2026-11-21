<?php

/**
 * Part of the Joomla Tracker Github Package
 *
 * @copyright  Copyright (C) 2012 - 2026 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JTracker\Github\Package\Repositories;

use Joomla\Github\Package\Repositories\Collaborators as JCollaborators;

/**
 * GitHub API Repositories Collaborators class, extended with the permission level lookup.
 *
 * @link   https://docs.github.com/en/rest/collaborators/collaborators
 *
 * @since  1.0
 */
class Collaborators extends JCollaborators
{
    /**
     * Get the permission level of a user for a repository.
     *
     * @param   string  $owner  The name of the owner of the GitHub repository.
     * @param   string  $repo   The name of the GitHub repository.
     * @param   string  $user   The name of the GitHub user.
     *
     * @return  string  One of admin, maintain, write, triage, read or none.
     *
     * @since   1.0
     * @throws  \Joomla\Http\Exception\UnexpectedResponseException
     */
    public function getPermission($owner, $repo, $user)
    {
        // Build the request path.
        $path = '/repos/' . $owner . '/' . $repo . '/collaborators/' . rawurlencode($user) . '/permission';

        $response = $this->processResponse($this->client->get($this->fetchUrl($path)));

        // The role name knows the "maintain" and "triage" roles, the legacy permission field only admin, write, read and none.
        if (isset($response->role_name) && \in_array($response->role_name, ['admin', 'maintain', 'write', 'triage', 'read'], true)) {
            return $response->role_name;
        }

        return $response->permission ?? 'none';
    }
}
