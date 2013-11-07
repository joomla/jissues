<?php
/**
 * Part of the Joomla Tracker's Text Application
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Text\Model;

use App\Text\Table\ArticlesTable;

use JTracker\Model\AbstractTrackerDatabaseModel;

/**
 * Article model class.
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
	 * @return  ArticlesTable
	 *
	 * @since   1.0
	 */
	public function getItem($id)
	{
		$table = new ArticlesTable($this->db);

		return $table->load($id)->getIterator();
	}
}
