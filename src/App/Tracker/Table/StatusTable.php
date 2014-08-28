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
 * @property   string   $status  status
 * @property   integer  $closed  closed
 *
 * @since  1.0
 */
class StatusTable extends AbstractDatabaseTable
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
		parent::__construct('#__status', 'id', $database);
	}

	/**
	 * Retrieves an array of IDs for whether a status is open or closed
	 *
	 * @param   boolean  $state  True to fetch closed status IDs, false to retrieve open status IDs
	 *
	 * @return  array
	 *
	 * @since   1.0
	 */
	public function getStateStatusIds($state)
	{
		// Build a query to fetch the status IDs based on open/close state
		return $this->db->setQuery(
			$this->db->getQuery(true)
				->select('id')
				->from($this->getTableName())
				->where('closed = ' . (int) $state)
		)->loadColumn();
	}
}
