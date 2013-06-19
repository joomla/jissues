<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Support\Model;

use App\Text\Table\ArticlesTable;
use JTracker\Model\AbstractTrackerDatabaseModel;

/**
 * Default model class for the Tracker component.
 *
 * @since  1.0
 */
class DefaultModel extends AbstractTrackerDatabaseModel
{
	/**
	 * Get an item.
	 *
	 * @param   string  $alias  The item alias.
	 *
	 * @return  ArticlesTable
	 *
	 * @since   1.0
	 */
	public function getItem($alias)
	{
		$table = new ArticlesTable($this->db);

		return $table->load(array('alias' => $alias));
	}
}
