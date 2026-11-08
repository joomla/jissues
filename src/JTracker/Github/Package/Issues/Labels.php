<?php

/**
 * Part of the Joomla Framework Github Package
 *
 * @copyright  Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace JTracker\Github\Package\Issues;

use Joomla\Github\Package\Issues\Labels as JoomlaLabels;

/**
 * GitHub API Labels class for the Joomla Framework.
 *
 * Adds pagination support to the label list.
 *
 * @documentation http://developer.github.com/v3/issues/labels/
 *
 * @since  1.0
 */
class Labels extends JoomlaLabels
{
    /**
     * List all labels for this repository.
     *
     * @param   string   $owner  The name of the owner of the GitHub repository.
     * @param   string   $repo   The name of the GitHub repository.
     * @param   integer  $page   The page number from which to get items.
     * @param   integer  $limit  The number of items on a page.
     *
     * @return  array
     *
     * @since   1.0
     */
    public function getList($owner, $repo, $page = 0, $limit = 0)
    {
        // Build the request path.
        $path = '/repos/' . $owner . '/' . $repo . '/labels';

        // Send the request.
        return $this->processResponse(
            $this->client->get($this->fetchUrl($path, $page, $limit))
        );
    }
}
