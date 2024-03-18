<?php

/**
 * Part of the Joomla Tracker Controller Package
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Controller\Concerns;

use Joomla\Uri\Uri;
use JTracker\Application\Application;
use JTracker\Model\ListfulModelInterface;
use JTracker\Pagination\TrackerPagination;

/**
 * Trait for controllers which are listful (supporting pagination)
 *
 * @since  1.0
 */
trait HasLists
{
    /**
     * Configure the pagination state for a listful model
     *
     * @param   Application            $app    The application to read the request state from
     * @param   ListfulModelInterface  $model  The model to set the state on
     *
     * @return  void
     *
     * @since   1.0
     */
    protected function configurePaginationState(Application $app, ListfulModelInterface $model): void
    {
        $limit = $app->getUserStateFromRequest('list.limit', 'list_limit', 20, 'uint');
        $page  = $app->getInput()->getUint('page');

        $value      = $page ? ($page - 1) * $limit : 0;
        $limitStart = ($limit != 0 ? (floor($value / $limit) * $limit) : 0);

        $state = $model->getState();
        $state->set('list.start', $limitStart);
        $state->set('list.limit', $limit);

        $model->setPagination(
            new TrackerPagination(new Uri($app->get('uri.request')))
        );
    }
}
