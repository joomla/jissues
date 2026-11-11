<?php

/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Model;

use Joomla\Database\QueryInterface;
use JTracker\Model\AbstractTrackerListModel;

/**
 * Model to get data for the categories list view
 *
 * @since  1.0
 */
class CategoriesModel extends AbstractTrackerListModel
{
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

        $projectId = $this->getProject()->project_id;

        $query->select('*')
            ->from($db->quoteName('#__issues_categories'))
            ->where($db->quoteName('project_id') . '=' . (int) $projectId)
            ->order($db->quoteName('title'));

        return $query;
    }
}
