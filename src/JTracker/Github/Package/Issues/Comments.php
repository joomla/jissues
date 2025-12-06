<?php

/**
 * Part of the Joomla Framework Github Package
 *
 * @copyright  Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace JTracker\Github\Package\Issues;

use JTracker\Github\Package;

/**
 * GitHub API Comments class for the Joomla Framework.
 *
 * The Issue Comments API supports listing, viewing, editing, and creating comments
 * on issues and pull requests.
 *
 * @documentation http://developer.github.com/v3/issues/comments/
 *
 * @since  1.0
 */
class Comments extends Package
{
    /**
     * List comments on an issue.
     *
     * @param   string              $owner    The name of the owner of the GitHub repository.
     * @param   string              $repo     The name of the GitHub repository.
     * @param   integer             $issueId  The issue number.
     * @param   integer             $page     The page number from which to get items.
     * @param   integer             $limit    The number of items on a page.
     * @param   \DateTimeInterface  $since    Only comments updated at or after this time are returned.
     *
     * @return  object
     *
     * @since   1.0
     * @throws  \DomainException
     */
    public function getList($owner, $repo, $issueId, $page = 0, $limit = 0, \DateTimeInterface $since = null)
    {
        // Build the request path.
        $path = '/repos/' . $owner . '/' . $repo . '/issues/' . (int) $issueId . '/comments';
        $path .= ($since) ? '?since=' . $since->format(\DateTime::RFC3339) : '';

        // Send the request.
        return $this->processResponse(
            $this->client->get($this->fetchUrl($path, $page, $limit))
        );
    }

    /**
     * List comments in a repository.
     *
     * @param   string              $owner      The name of the owner of the GitHub repository.
     * @param   string              $repo       The name of the GitHub repository.
     * @param   string              $sort       The sort field - created or updated.
     * @param   string              $direction  The sort order- asc or desc. Ignored without sort parameter.
     * @param   \DateTimeInterface  $since      Only comments updated at or after this time are returned.
     *
     * @return  object
     *
     * @since   1.0
     * @throws  \UnexpectedValueException
     * @throws  \DomainException
     */
    public function getRepositoryList($owner, $repo, $sort = 'created', $direction = 'asc', \DateTimeInterface $since = null)
    {
        // Build the request path.
        $path = '/repos/' . $owner . '/' . $repo . '/issues/comments';

        if (\in_array($sort, ['created', 'updated']) == false) {
            throw new \UnexpectedValueException(
                \sprintf(
                    '%1$s - sort field must be "created" or "updated"',
                    __METHOD__
                )
            );
        }

        if (\in_array($direction, ['asc', 'desc']) == false) {
            throw new \UnexpectedValueException(
                \sprintf(
                    '%1$s - direction field must be "asc" or "desc"',
                    __METHOD__
                )
            );
        }

        $path .= '?sort=' . $sort;
        $path .= '&direction=' . $direction;

        if ($since) {
            $path .= '&since=' . $since->format(\DateTime::RFC3339);
        }

        // Send the request.
        return $this->processResponse($this->client->get($this->fetchUrl($path)));
    }

    /**
     * Get a single comment.
     *
     * @param   string   $owner  The name of the owner of the GitHub repository.
     * @param   string   $repo   The name of the GitHub repository.
     * @param   integer  $id     The comment id.
     *
     * @return  object
     *
     * @since   1.0
     * @throws  \DomainException
     */
    public function get($owner, $repo, $id)
    {
        // Build the request path.
        $path = '/repos/' . $owner . '/' . $repo . '/issues/comments/' . (int) $id;

        // Send the request.
        return $this->processResponse(
            $this->client->get($this->fetchUrl($path))
        );
    }

    /**
     * Edit a comment.
     *
     * @param   string   $user       The name of the owner of the GitHub repository.
     * @param   string   $repo       The name of the GitHub repository.
     * @param   integer  $commentId  The id of the comment to update.
     * @param   string   $body       The new body text for the comment.
     *
     * @return  object
     *
     * @since   1.0
     * @throws  \DomainException
     */
    public function edit($user, $repo, $commentId, $body)
    {
        // Build the request path.
        $path = '/repos/' . $user . '/' . $repo . '/issues/comments/' . (int) $commentId;

        // Build the request data.
        $data = json_encode(
            [
                'body' => $body,
            ]
        );

        // Send the request.
        return $this->processResponse(
            $this->client->patch($this->fetchUrl($path), $data)
        );
    }

    /**
     * Create a comment.
     *
     * @param   string   $user     The name of the owner of the GitHub repository.
     * @param   string   $repo     The name of the GitHub repository.
     * @param   integer  $issueId  The issue number.
     * @param   string   $body     The comment body text.
     *
     * @return  object
     *
     * @since   1.0
     * @throws  \DomainException
     */
    public function create($user, $repo, $issueId, $body)
    {
        // Build the request path.
        $path = '/repos/' . $user . '/' . $repo . '/issues/' . (int) $issueId . '/comments';

        // Build the request data.
        $data = json_encode(
            [
                'body' => $body,
            ]
        );

        // Send the request.
        return $this->processResponse(
            $this->client->post($this->fetchUrl($path), $data),
            201
        );
    }

    /**
     * Delete a comment.
     *
     * @param   string   $user       The name of the owner of the GitHub repository.
     * @param   string   $repo       The name of the GitHub repository.
     * @param   integer  $commentId  The id of the comment to delete.
     *
     * @return  boolean
     *
     * @since   1.0
     * @throws  \DomainException
     */
    public function delete($user, $repo, $commentId)
    {
        // Build the request path.
        $path = '/repos/' . $user . '/' . $repo . '/issues/comments/' . (int) $commentId;

        // Send the request.
        $this->processResponse(
            $this->client->delete($this->fetchUrl($path)),
            204
        );

        return true;
    }
}
