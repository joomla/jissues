<?php
/**
 * @copyright  Copyright (C) 2013 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Text\Model;

use App\Text\Table\ArticlesTable;
use JTracker\Authentication\Database\TableUsers;
use JTracker\Model\AbstractTrackerDatabaseModel;

/**
 * User model class for the Users component.
 *
 * @since  1.0
 */
class ArticleModel extends AbstractTrackerDatabaseModel
{
	/**
	 * Get an item.
	 *
	 * @param   integer  $id  The item id.
	 *
	 * @return  TableUsers
	 *
	 * @since   1.0
	 */
	public function getItem($id)
	{
		$table = new ArticlesTable($this->db);

		return $table->load($id)->getIterator();
	}
}
