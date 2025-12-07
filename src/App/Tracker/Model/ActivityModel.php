<?php

/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Model;

use App\Tracker\Table\ActivitiesTable;
use JTracker\Model\AbstractTrackerDatabaseModel;

/**
 * Model to get data for the issue list view
 *
 * @since  1.0
 */
class ActivityModel extends AbstractTrackerDatabaseModel
{
    /**
     * Add a new event and store it to the database.
     *
     * @param   string   $event       The event name.
     * @param   string   $dateTime    Date and time.
     * @param   string   $userName    User name.
     * @param   integer  $projectId   Project id.
     * @param   integer  $itemNumber  THE item number.
     * @param   integer  $commentId   The comment id
     * @param   string   $text        The parsed html comment text.
     * @param   string   $textRaw     The raw comment text.
     *
     * @return  ActivitiesTable
     *
     * @since   1.0
     */
    public function addActivityEvent($event, $dateTime, $userName, $projectId, $itemNumber, $commentId = null, $text = '', $textRaw = '')
    {
        return (new ActivitiesTable($this->db))->save(
            [
                'created_date'  => (new \DateTime($dateTime))->format($this->db->getDateFormat()),
                'event'         => $event,
                'user'          => $userName,
                'project_id'    => (int) $projectId,
                'issue_number'  => (int) $itemNumber,
                'gh_comment_id' => (int) $commentId,
                'text'          => $text,
                'text_raw'      => $textRaw,
            ]
        );
    }
}
