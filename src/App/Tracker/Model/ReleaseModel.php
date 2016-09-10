<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Model;

use App\Tracker\Table\ReleasesTable;

use JTracker\Model\AbstractTrackerDatabaseModel;

/**
 * Model to get data for the release item view
 *
 * @since  1.0
 */
class ReleaseModel extends AbstractTrackerDatabaseModel
{
	/**
	 * Context string for the model type.  This is used to handle uniqueness
	 * when dealing with the getStoreId() method and caching data structures.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $context = 'com_tracker.release';

	/**
	 * Add the release.
	 *
	 * @param   array  $src  The source.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	public function add(array $src)
	{
		// Store the issue
		$table = (new ReleasesTable($this->db))
			->save($src);

		return $this;
	}
}
