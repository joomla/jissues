<?php
/**
 * Part of the Joomla Tracker's Activity Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Activity\Model;

use Joomla\Database\DatabaseIterator;

use JTracker\Model\AbstractTrackerDatabaseModel;

/**
 * Model to get the project snapshot
 *
 * @since  1.0
 */
class SnapshotModel extends AbstractTrackerDatabaseModel
{
	/**
	 * Get the open issues
	 *
	 * @return  DatabaseIterator
	 *
	 * @since   1.0
	 */
	public function getOpenIssues()
	{
		// Create a new query object.
		$db    = $this->getDb();
		$query = $db->getQuery(true);

		$query->select('a.*')
			->from($db->quoteName('#__issues', 'a'))
			->join('LEFT', '#__status AS s ON a.status = s.id')
			->where('a.project_id = ' . (int) $this->getProject()->project_id)
			->where('s.closed = 0');

		$db->setQuery($query);

		return $db->getIterator();
	}
}
