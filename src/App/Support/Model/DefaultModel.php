<?php

/**
 * Part of the Joomla Tracker's Support Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Support\Model;

use App\Text\Table\ArticlesTable;
use JTracker\Model\AbstractTrackerDatabaseModel;

/**
 * Default model class for the Support component.
 *
 * @since  1.0
 */
class DefaultModel extends AbstractTrackerDatabaseModel
{
    /**
     * Get an item.
     *
     * @param   string  $alias  The item alias.
     * @param   string  $path   The path to the item.
     *
     * @return  ArticlesTable
     *
     * @since   1.0
     */
    public function getItem($alias, $path = '')
    {
        return (new ArticlesTable($this->db))
            ->load(['alias' => $alias, 'path' => $path, 'is_file' => 1]);
    }
}
