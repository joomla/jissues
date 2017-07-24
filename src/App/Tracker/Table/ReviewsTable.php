<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Table;

use Joomla\Database\DatabaseDriver;

use JTracker\Database\AbstractDatabaseTable;

/**
 * Table interface class for the #__status table
 *
 * @property   integer  $id                 ID
 * @property   integer  $review_state       Review state
 * @property   string   $reviewed_by        The username of the person who made the review
 * @property   string   $review_comment     The comment associated with the review
 * @property   string   $review_submitted   The date the review was submitted on
 * @property   string   $dismissed_by       The username of the person who made the review
 * @property   string   $dismissed_on       The date the review was dismissed on
 * @property   string   $dismissed_comment  The comment associated with the review
 *
 * @since  1.0
 */
class ReviewsTable extends AbstractDatabaseTable
{
	/**
	 * Constructor
	 *
	 * @param   DatabaseDriver  $database  A database connector object.
	 *
	 * @since   1.0
	 */
	public function __construct(DatabaseDriver $database)
	{
		parent::__construct('#__issue_reviews', 'id', $database);
	}
}
