<?php
/**
 * Part of the Joomla! Tracker
 *
 * @copyright  Copyright (C) 2015 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Projects\Table;

use Joomla\Database\DatabaseDriver;

use JTracker\Database\AbstractDatabaseTable;

/**
 * Table interface class for the #__tracker_actions table
 *
 * @property   integer  $id          PK
 * @property   integer  $project_id  Project ID.
 * @property   string   $type        Action type.
 * @property   string   $name        Action name.
 * @property   string   $params      JSON encoded param string.
 *
 * @since  1.0
 */
class ActionsTable extends AbstractDatabaseTable
{
	/**
	 * Constructor
	 *
	 * @param   DatabaseDriver  $database  A database connector object
	 *
	 * @since   1.0
	 */
	public function __construct(DatabaseDriver $database)
	{
		parent::__construct('#__tracker_actions', 'id', $database);
	}
}
