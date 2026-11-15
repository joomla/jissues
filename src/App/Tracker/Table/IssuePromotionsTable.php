<?php

/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2026 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Table;

use Joomla\Database\DatabaseInterface;
use JTracker\Database\AbstractDatabaseTable;

/**
 * Table interface class for the #__issue_promotions table
 *
 * Records which items were posted to the testing channel, so that each item is posted only once per label.
 *
 * @property   integer  $id           PK
 * @property   integer  $issue_id     PK of the issue in the issues table
 * @property   string   $label        The label that triggered the promotion
 * @property   string   $posted_date  When the item was posted
 * @property   string   $posted_by    GitHub login of the user who set the label
 *
 * @since  1.0
 */
class IssuePromotionsTable extends AbstractDatabaseTable
{
    /**
     * Constructor
     *
     * @param   DatabaseInterface  $database  A database connector object.
     *
     * @since   1.0
     */
    public function __construct(DatabaseInterface $database)
    {
        parent::__construct('#__issue_promotions', 'id', $database);
    }
}
