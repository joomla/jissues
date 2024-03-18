<?php

/**
 * Part of the Joomla Tracker Model Package
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Model;

use Joomla\Model\StatefulModelInterface;
use JTracker\Pagination\TrackerPagination;

/**
 * Interface describing a model which supports paginated lists
 *
 * @since  1.0
 */
interface ListfulModelInterface extends StatefulModelInterface
{
    /**
     * Set the pagination object.
     *
     * @param   TrackerPagination  $pagination  The pagination object.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function setPagination(TrackerPagination $pagination): void;

    /**
     * Get the pagination object for the data set.
     *
     * @return  TrackerPagination
     *
     * @since   1.0
     * @throws  \UnexpectedValueException if the pagination object has not been set to the model
     */
    public function getPagination(): TrackerPagination;

    /**
     * Get the starting number of items for the data set.
     *
     * @return  integer  The starting number of items available in the data set.
     *
     * @since   1.0
     */
    public function getStart(): int;

    /**
     * Get the total number of items for the data set.
     *
     * @return  integer  The total number of items available in the data set.
     *
     * @since   1.0
     */
    public function getTotal(): int;

    /**
     * Get an array of data items with pagination filters applied.
     *
     * @return  object[]
     *
     * @since   1.0
     */
    public function getItems(): array;
}
